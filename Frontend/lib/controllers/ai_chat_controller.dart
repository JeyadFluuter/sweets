import 'package:flutter/material.dart';
import 'package:flutter/scheduler.dart';
import 'package:get/get.dart';
import 'package:sweet_store/controllers/ai_gate_controller.dart';
import 'package:sweet_store/controllers/cart_controller.dart';
import 'package:sweet_store/core/constants/app_colors.dart';
import 'package:sweet_store/core/network/api_exception.dart';
import 'package:sweet_store/core/routes/app_routes.dart';
import 'package:sweet_store/models/ai_chat_model.dart';
import 'package:sweet_store/services/ai_service.dart';
import 'package:sweet_store/services/cart_service.dart';
import 'package:sweet_store/services/catalog_service.dart';

class AiChatController extends GetxController {
  AiChatController(this._ai);

  final AiService _ai;

  final status = AiStatusModel.disabled().obs;
  final messages = <AiChatMessageModel>[].obs;
  final sending = false.obs;
  final loadingStatus = true.obs;
  final input = ''.obs;
  final conversationId = RxnInt();
  final addingSuggestionId = RxnInt();
  final addingOffer = false.obs;

  final textController = TextEditingController();
  final scrollController = ScrollController();

  static const quickPrompts = <String>[
    'نبي حلويات لمناسبة',
    'نبي بقلاوة',
    'عندي خطوبة و 50 شخص',
    'شن عندكم من عروض؟',
  ];

  static const collectingFollowUps = <String>[
    '20 شخص',
    'ميزانية 250 د.ل',
    'شن أنواع البقلاوة عندكم',
    'نبي كنافة',
  ];

  static const proposalFollowUps = <String>[
    'خلي الميزانية أقل',
    'زوّد الكميات شوي',
    'خلّي العرض أحلى أكثر',
    'ورّيني خيار أوفر',
  ];

  List<String> followUpsFor(AiReplyStatus status) {
    if (status == AiReplyStatus.collectingInfo) {
      return collectingFollowUps;
    }
    if (status.showsOffer) {
      return proposalFollowUps;
    }
    return const [];
  }

  @override
  void onInit() {
    super.onInit();
    if (!Get.isRegistered<CartController>()) {
      Get.put(
        CartController(Get.find<CartService>(), Get.find<CatalogService>()),
        permanent: true,
      );
    }
    Future.microtask(bootstrap);
  }

  @override
  void onClose() {
    textController.dispose();
    scrollController.dispose();
    super.onClose();
  }

  Future<void> bootstrap() async {
    await refreshStatus();
    _ensureWelcomeMessage();
  }

  Future<void> refreshStatus({bool silent = false}) async {
    if (!silent) loadingStatus.value = true;
    try {
      status.value = await _ai.fetchStatus();
      if (Get.isRegistered<AiGateController>()) {
        Get.find<AiGateController>().status.value = status.value;
      }
    } catch (_) {
      if (!silent) {
        status.value = AiStatusModel.disabled();
      }
    } finally {
      if (!silent) loadingStatus.value = false;
    }
  }

  void _ensureWelcomeMessage() {
    if (messages.isNotEmpty) return;
    messages.add(
      AiChatMessageModel(
        role: 'assistant',
        content: status.value.enabled
            ? 'مرحباً! أنا مستشار Sweet Store. قلّي شن تبي (مناسبة، صنف، أو عدد ضيوف) ونكمّل خطوة بخطوة لحد ما نثبت عرض يناسب ميزانيتك.'
            : 'المساعد غير مفعّل حالياً من لوحة التحكم. يمكنك تصفح الأصناف يدوياً.',
        status: AiReplyStatus.collectingInfo,
      ),
    );
  }

  void onInputChanged(String value) => input.value = value;

  Future<void> sendQuick(String prompt) async {
    textController.text = prompt;
    input.value = prompt;
    await send();
  }

  Future<void> send() async {
    final text = textController.text.trim();
    if (text.isEmpty || sending.value) return;
    if (!status.value.enabled) {
      Get.snackbar(
        'المساعد',
        'المساعد غير مفعّل حالياً',
        snackPosition: SnackPosition.BOTTOM,
      );
      return;
    }

    textController.clear();
    input.value = '';
    sending.value = true;

    final userMessage = AiChatMessageModel(role: 'user', content: text);
    final typingMessage = AiChatMessageModel(
      role: 'assistant',
      content: '',
      isTyping: true,
    );
    messages.add(userMessage);
    messages.add(typingMessage);
    _scrollToEnd();

    try {
      final reply = await _ai.chat(
        message: text,
        conversationId: conversationId.value,
      );
      if (reply.conversationId > 0) {
        conversationId.value = reply.conversationId;
      }
      _replaceTypingWith(
        AiChatMessageModel(
          role: 'assistant',
          content: reply.message.trim().isEmpty
              ? 'فهمت طلبك. تحب نكمل ونرتّب العرض خطوة بخطوة؟'
              : reply.message,
          suggestions: reply.suggestions,
          status: reply.status,
          estimatedTotal: reply.estimatedTotal,
        ),
      );
    } on ApiException catch (e) {
      _replaceTypingWith(
        AiChatMessageModel(role: 'assistant', content: e.firstError),
      );
    } catch (_) {
      _replaceTypingWith(
        AiChatMessageModel(
          role: 'assistant',
          content: 'تعذر الاتصال الآن. أعد المحاولة بعد لحظات، أو صف طلبك بجملة أوضح.',
        ),
      );
    } finally {
      sending.value = false;
      _scrollToEnd();
    }
  }

  void _replaceTypingWith(AiChatMessageModel message) {
    final next = <AiChatMessageModel>[];
    var replaced = false;
    for (final item in messages) {
      if (!replaced && item.isTyping) {
        next.add(message);
        replaced = true;
      } else {
        next.add(item);
      }
    }
    if (!replaced) next.add(message);
    messages.assignAll(next);
  }

  Future<void> addSuggestion(AiSuggestionModel suggestion) async {
    if (addingSuggestionId.value != null || addingOffer.value) return;
    addingSuggestionId.value = suggestion.id;
    final cart = Get.find<CartController>();
    final ok = suggestion.isCollection
        ? await cart.addCollection(suggestion.id, quantity: suggestion.quantity)
        : await cart.addProduct(suggestion.id, quantity: suggestion.quantity);
    addingSuggestionId.value = null;

    if (ok) {
      _showAddedSnackbar(suggestion.name);
    }
  }

  Future<void> addOffer(List<AiSuggestionModel> suggestions) async {
    if (suggestions.isEmpty || addingOffer.value || addingSuggestionId.value != null) {
      return;
    }
    addingOffer.value = true;
    final cart = Get.find<CartController>();
    var added = 0;
    for (final suggestion in suggestions) {
      final ok = suggestion.isCollection
          ? await cart.addCollection(suggestion.id, quantity: suggestion.quantity)
          : await cart.addProduct(suggestion.id, quantity: suggestion.quantity);
      if (ok) added++;
    }
    addingOffer.value = false;

    if (added > 0) {
      _showAddedSnackbar('العرض ($added أصناف)');
    }
  }

  void _showAddedSnackbar(String label) {
    Get.snackbar(
      'تمت الإضافة',
      'أُضيف $label إلى السلة',
      snackPosition: SnackPosition.BOTTOM,
      backgroundColor: AppColors.mint,
      colorText: Colors.white,
      mainButton: TextButton(
        onPressed: () {
          Get.closeCurrentSnackbar();
          Get.toNamed(AppRoutes.cart);
        },
        child: const Text('السلة', style: TextStyle(color: Colors.white)),
      ),
    );
  }

  void openSuggestion(AiSuggestionModel suggestion) {
    if (suggestion.isCollection) {
      Get.toNamed(
        AppRoutes.collectionDetail.replaceFirst(':id', '${suggestion.id}'),
      );
    } else {
      Get.toNamed(
        AppRoutes.productDetail.replaceFirst(':id', '${suggestion.id}'),
      );
    }
  }

  void _scrollToEnd() {
    void jump() {
      if (!scrollController.hasClients) return;
      final target = scrollController.position.maxScrollExtent;
      if (target <= 0) return;
      scrollController.jumpTo(target);
    }

    SchedulerBinding.instance.addPostFrameCallback((_) {
      jump();
      SchedulerBinding.instance.addPostFrameCallback((_) => jump());
    });
  }
}
