import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:sweet_store/controllers/ai_chat_controller.dart';
import 'package:sweet_store/core/constants/app_colors.dart';
import 'package:sweet_store/models/ai_chat_model.dart';
import 'package:sweet_store/services/ai_service.dart';
import 'package:sweet_store/widgets/skeleton_box.dart';

class AiChatView extends StatelessWidget {
  const AiChatView({super.key});

  @override
  Widget build(BuildContext context) {
    final controller = Get.isRegistered<AiChatController>()
        ? Get.find<AiChatController>()
        : Get.put(AiChatController(Get.find<AiService>()), permanent: true);

    return Scaffold(
      backgroundColor: const Color(0xFFF3F8F5),
      body: Column(
        children: [
          _AiHeader(controller: controller),
          Expanded(
            child: DecoratedBox(
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  colors: [Color(0xFFF3F8F5), Color(0xFFEEF5F0), Color(0xFFF7FAF8)],
                ),
              ),
              child: _MessagesPane(controller: controller),
            ),
          ),
          _Composer(controller: controller),
        ],
      ),
    );
  }
}

class _AiHeader extends StatelessWidget {
  const _AiHeader({required this.controller});

  final AiChatController controller;

  @override
  Widget build(BuildContext context) {
    final top = MediaQuery.paddingOf(context).top;
    return Container(
      width: double.infinity,
      padding: EdgeInsets.fromLTRB(18, top + 12, 18, 16),
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topRight,
          end: Alignment.bottomLeft,
          colors: [Color(0xFF149A5C), Color(0xFF2ECC87), Color(0xFFF6B73C)],
          stops: [0.0, 0.58, 1.0],
        ),
        borderRadius: BorderRadius.vertical(bottom: Radius.circular(26)),
      ),
      child: Obx(() {
        final enabled = controller.status.value.enabled;
        return Row(
          children: [
            Container(
              width: 50,
              height: 50,
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.2),
                shape: BoxShape.circle,
                border: Border.all(color: Colors.white.withValues(alpha: 0.35)),
              ),
              child: const Icon(
                Icons.auto_awesome_rounded,
                color: Colors.white,
                size: 24,
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'مساعد Sweet Store',
                    style: TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.w800,
                      fontSize: 17,
                    ),
                  ),
                  const SizedBox(height: 3),
                  Text(
                    enabled
                        ? (controller.status.value.ready
                            ? 'جاهز يساعدك خطوة بخطوة'
                            : 'مفعّل — تحقق من اتصال المزوّد إن تعطل الرد')
                        : 'متوقف مؤقتاً من لوحة التحكم',
                    style: TextStyle(
                      color: Colors.white.withValues(alpha: 0.92),
                      fontWeight: FontWeight.w600,
                      fontSize: 12,
                    ),
                  ),
                ],
              ),
            ),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.18),
                borderRadius: BorderRadius.circular(20),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    width: 7,
                    height: 7,
                    decoration: BoxDecoration(
                      color: enabled
                          ? (controller.status.value.ready
                              ? Colors.white
                              : const Color(0xFFFFE08A))
                          : const Color(0xFFFFD0C8),
                      shape: BoxShape.circle,
                    ),
                  ),
                  const SizedBox(width: 6),
                  Text(
                    enabled
                        ? (controller.status.value.ready ? 'متصل' : 'مفعّل')
                        : 'متوقف',
                    style: const TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.w700,
                      fontSize: 11,
                    ),
                  ),
                ],
              ),
            ),
          ],
        );
      }),
    );
  }
}

class _MessagesPane extends StatelessWidget {
  const _MessagesPane({required this.controller});

  final AiChatController controller;

  @override
  Widget build(BuildContext context) {
    return Obx(() {
      if (controller.loadingStatus.value && controller.messages.isEmpty) {
        return const AiChatSkeleton();
      }

      final items = List<AiChatMessageModel>.from(controller.messages);
      final showPrompts =
          items.length <= 1 && controller.status.value.enabled;

      AiChatMessageModel? lastAssistant;
      for (var i = items.length - 1; i >= 0; i--) {
        final item = items[i];
        if (item.isAssistant && !item.isTyping) {
          lastAssistant = item;
          break;
        }
      }

      final followUps = lastAssistant == null
          ? const <String>[]
          : controller.followUpsFor(lastAssistant.status);
      final showFollowUps = !showPrompts &&
          !controller.sending.value &&
          controller.status.value.enabled &&
          followUps.isNotEmpty;

      return Column(
        children: [
          if (showPrompts)
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 14, 16, 0),
              child: _QuickPrompts(
                onTap: controller.sendQuick,
                enabled: !controller.sending.value,
              ),
            ),
          Expanded(
            child: ListView.builder(
              controller: controller.scrollController,
              primary: false,
              padding: const EdgeInsets.fromLTRB(14, 14, 14, 10),
              itemCount: items.length,
              itemBuilder: (context, index) {
                final message = items[index];
                return _MessageBubble(
                  key: ValueKey(
                    '${message.role}-${message.createdAt.microsecondsSinceEpoch}-${message.suggestions.length}-$index',
                  ),
                  message: message,
                  onAdd: controller.addSuggestion,
                  onAddOffer: controller.addOffer,
                  onOpen: controller.openSuggestion,
                  addingId: controller.addingSuggestionId.value,
                  addingOffer: controller.addingOffer.value,
                );
              },
            ),
          ),
          if (showFollowUps)
            Padding(
              padding: const EdgeInsets.fromLTRB(14, 0, 14, 8),
              child: _FollowUpBar(
                prompts: followUps,
                onTap: controller.sendQuick,
                enabled: !controller.sending.value,
              ),
            ),
        ],
      );
    });
  }
}

class _QuickPrompts extends StatelessWidget {
  const _QuickPrompts({required this.onTap, required this.enabled});

  final ValueChanged<String> onTap;
  final bool enabled;

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'ابدأ بجملة قصيرة — نكمّل معاً',
          style: TextStyle(
            fontWeight: FontWeight.w800,
            fontSize: 13,
            color: AppColors.textMuted,
          ),
        ),
        const SizedBox(height: 10),
        Wrap(
          spacing: 8,
          runSpacing: 8,
          children: AiChatController.quickPrompts.map((prompt) {
            return Material(
              color: Colors.white,
              borderRadius: BorderRadius.circular(18),
              child: InkWell(
                onTap: enabled ? () => onTap(prompt) : null,
                borderRadius: BorderRadius.circular(18),
                child: Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 12,
                    vertical: 10,
                  ),
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(18),
                    border: Border.all(color: const Color(0xFFD9E8DF)),
                  ),
                  child: Text(
                    prompt,
                    style: const TextStyle(
                      fontWeight: FontWeight.w700,
                      fontSize: 12.5,
                      color: AppColors.textDark,
                    ),
                  ),
                ),
              ),
            );
          }).toList(),
        ),
      ],
    );
  }
}

class _FollowUpBar extends StatelessWidget {
  const _FollowUpBar({
    required this.prompts,
    required this.onTap,
    required this.enabled,
  });

  final List<String> prompts;
  final ValueChanged<String> onTap;
  final bool enabled;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 40,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: prompts.length,
        separatorBuilder: (_, __) => const SizedBox(width: 8),
        itemBuilder: (context, index) {
          final label = prompts[index];
          return Material(
            color: const Color(0xFFE8F7EF),
            borderRadius: BorderRadius.circular(20),
            child: InkWell(
              onTap: enabled ? () => onTap(label) : null,
              borderRadius: BorderRadius.circular(20),
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 14),
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(20),
                  border: Border.all(color: const Color(0xFFBFE8D2)),
                ),
                child: Text(
                  label,
                  style: const TextStyle(
                    fontWeight: FontWeight.w700,
                    fontSize: 12.5,
                    color: AppColors.mintDark,
                  ),
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}

class _MessageBubble extends StatelessWidget {
  const _MessageBubble({
    super.key,
    required this.message,
    required this.onAdd,
    required this.onAddOffer,
    required this.onOpen,
    this.addingId,
    this.addingOffer = false,
  });

  final AiChatMessageModel message;
  final ValueChanged<AiSuggestionModel> onAdd;
  final ValueChanged<List<AiSuggestionModel>> onAddOffer;
  final ValueChanged<AiSuggestionModel> onOpen;
  final int? addingId;
  final bool addingOffer;

  @override
  Widget build(BuildContext context) {
    final isUser = message.isUser;
    final screenWidth = MediaQuery.sizeOf(context).width;
    final bubbleMax = screenWidth * 0.8;
    final offerWidth = screenWidth - 28;

    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: Column(
        crossAxisAlignment:
            isUser ? CrossAxisAlignment.end : CrossAxisAlignment.start,
        children: [
          if (!isUser)
            const Padding(
              padding: EdgeInsets.only(bottom: 5, right: 4),
              child: Text(
                'المساعد',
                style: TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w700,
                  color: AppColors.textHint,
                ),
              ),
            ),
          ConstrainedBox(
            constraints: BoxConstraints(maxWidth: bubbleMax),
            child: DecoratedBox(
              decoration: BoxDecoration(
                gradient: isUser
                    ? const LinearGradient(
                        colors: [Color(0xFF1DB56E), Color(0xFF2ECC87)],
                      )
                    : null,
                color: isUser ? null : Colors.white,
                borderRadius: BorderRadius.only(
                  topLeft: const Radius.circular(18),
                  topRight: const Radius.circular(18),
                  bottomLeft: Radius.circular(isUser ? 18 : 5),
                  bottomRight: Radius.circular(isUser ? 5 : 18),
                ),
                border: isUser
                    ? null
                    : Border.all(color: const Color(0xFFE6EEE9)),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: isUser ? 0.06 : 0.03),
                    blurRadius: isUser ? 10 : 8,
                    offset: const Offset(0, 3),
                  ),
                ],
              ),
              child: Padding(
                padding: const EdgeInsets.symmetric(
                  horizontal: 14,
                  vertical: 12,
                ),
                child: message.isTyping
                    ? const _TypingLabel()
                    : Text(
                        message.content,
                        style: TextStyle(
                          color: isUser ? Colors.white : AppColors.textDark,
                          height: 1.55,
                          fontWeight: FontWeight.w600,
                          fontSize: 14.2,
                        ),
                      ),
              ),
            ),
          ),
          if (!message.isTyping && message.suggestions.isNotEmpty) ...[
            const SizedBox(height: 10),
            SizedBox(
              width: offerWidth,
              child: _OfferPanel(
                suggestions: message.suggestions,
                addingId: addingId,
                addingOffer: addingOffer,
                onAdd: onAdd,
                onAddOffer: onAddOffer,
                onOpen: onOpen,
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _TypingLabel extends StatelessWidget {
  const _TypingLabel();

  @override
  Widget build(BuildContext context) {
    return const Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        SizedBox(
          width: 14,
          height: 14,
          child: CircularProgressIndicator(
            strokeWidth: 2,
            color: AppColors.mintDark,
          ),
        ),
        SizedBox(width: 8),
        Text(
          'يحضّر عرضك...',
          style: TextStyle(
            color: AppColors.textMuted,
            fontWeight: FontWeight.w700,
            fontSize: 13,
          ),
        ),
      ],
    );
  }
}

class _OfferPanel extends StatelessWidget {
  const _OfferPanel({
    required this.suggestions,
    required this.onAdd,
    required this.onAddOffer,
    required this.onOpen,
    this.addingId,
    this.addingOffer = false,
  });

  final List<AiSuggestionModel> suggestions;
  final ValueChanged<AiSuggestionModel> onAdd;
  final ValueChanged<List<AiSuggestionModel>> onAddOffer;
  final ValueChanged<AiSuggestionModel> onOpen;
  final int? addingId;
  final bool addingOffer;

  @override
  Widget build(BuildContext context) {
    final total = suggestions.fold<num>(0, (sum, s) => sum + s.lineTotal);
    final totalText = total % 1 == 0
        ? total.toStringAsFixed(0)
        : total.toStringAsFixed(2);

    return DecoratedBox(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFD7EBDD)),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF1DB56E).withValues(alpha: 0.06),
            blurRadius: 14,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Container(
            padding: const EdgeInsets.fromLTRB(14, 12, 14, 12),
            decoration: const BoxDecoration(
              color: Color(0xFFEEF9F2),
              borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
            ),
            child: Row(
              children: [
                Container(
                  width: 34,
                  height: 34,
                  decoration: BoxDecoration(
                    color: AppColors.mint.withValues(alpha: 0.18),
                    borderRadius: BorderRadius.circular(11),
                  ),
                  child: const Icon(
                    Icons.local_offer_rounded,
                    color: AppColors.mintDark,
                    size: 18,
                  ),
                ),
                const SizedBox(width: 10),
                const Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'عرض مقترح',
                        style: TextStyle(
                          fontWeight: FontWeight.w800,
                          fontSize: 14,
                          color: AppColors.textDark,
                        ),
                      ),
                      SizedBox(height: 2),
                      Text(
                        'كميات متناسقة جاهزة للإضافة',
                        style: TextStyle(
                          fontWeight: FontWeight.w600,
                          fontSize: 11.5,
                          color: AppColors.textMuted,
                        ),
                      ),
                    ],
                  ),
                ),
                Text(
                  '$totalText د.ل',
                  style: const TextStyle(
                    fontWeight: FontWeight.w900,
                    fontSize: 15,
                    color: AppColors.mintDark,
                  ),
                ),
              ],
            ),
          ),
          ...suggestions.asMap().entries.map((entry) {
            final isLast = entry.key == suggestions.length - 1;
            return Column(
              children: [
                _SuggestionTile(
                  suggestion: entry.value,
                  busy: addingId == entry.value.id,
                  disabled: addingOffer,
                  onAdd: () => onAdd(entry.value),
                  onOpen: () => onOpen(entry.value),
                ),
                if (!isLast)
                  const Divider(
                    height: 1,
                    indent: 14,
                    endIndent: 14,
                    color: Color(0xFFEEF2EF),
                  ),
              ],
            );
          }),
          Padding(
            padding: const EdgeInsets.fromLTRB(12, 4, 12, 12),
            child: SizedBox(
              height: 44,
              child: FilledButton(
                onPressed: addingOffer || addingId != null
                    ? null
                    : () => onAddOffer(suggestions),
                style: FilledButton.styleFrom(
                  backgroundColor: AppColors.mintDark,
                  foregroundColor: Colors.white,
                  disabledBackgroundColor: AppColors.fieldFill,
                  elevation: 0,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(14),
                  ),
                ),
                child: addingOffer
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: Colors.white,
                        ),
                      )
                    : const Text(
                        'أضف العرض كاملاً للسلة',
                        style: TextStyle(
                          fontWeight: FontWeight.w800,
                          fontSize: 13.5,
                        ),
                      ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _SuggestionTile extends StatelessWidget {
  const _SuggestionTile({
    required this.suggestion,
    required this.onAdd,
    required this.onOpen,
    this.busy = false,
    this.disabled = false,
  });

  final AiSuggestionModel suggestion;
  final VoidCallback onAdd;
  final VoidCallback onOpen;
  final bool busy;
  final bool disabled;

  @override
  Widget build(BuildContext context) {
    final subtitle = suggestion.reason?.trim().isNotEmpty == true
        ? suggestion.reason!.trim()
        : (suggestion.isCollection ? 'باقة جاهزة' : 'صنف من المتجر');

    return Padding(
      padding: const EdgeInsets.fromLTRB(12, 10, 12, 10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          GestureDetector(
            onTap: onOpen,
            behavior: HitTestBehavior.opaque,
            child: ClipRRect(
              borderRadius: BorderRadius.circular(14),
              child: SizedBox(
                width: 58,
                height: 58,
                child: Stack(
                  fit: StackFit.expand,
                  children: [
                    suggestion.imageUrl == null
                        ? ColoredBox(
                            color: AppColors.fieldFill,
                            child: Icon(
                              suggestion.isCollection
                                  ? Icons.card_giftcard_rounded
                                  : Icons.cake_outlined,
                              color: AppColors.coralSoft,
                            ),
                          )
                        : Image.network(
                            suggestion.imageUrl!,
                            fit: BoxFit.cover,
                            errorBuilder: (context, error, stackTrace) =>
                                ColoredBox(
                              color: AppColors.fieldFill,
                              child: Icon(
                                suggestion.isCollection
                                    ? Icons.card_giftcard_rounded
                                    : Icons.cake_outlined,
                                color: AppColors.coralSoft,
                              ),
                            ),
                          ),
                    Positioned(
                      right: 4,
                      top: 4,
                      child: Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 6,
                          vertical: 2,
                        ),
                        decoration: BoxDecoration(
                          color: Colors.black.withValues(alpha: 0.55),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          suggestion.isCollection ? 'باقة' : 'صنف',
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 9.5,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: GestureDetector(
              onTap: onOpen,
              behavior: HitTestBehavior.opaque,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    suggestion.name.isEmpty
                        ? (suggestion.isCollection ? 'باقة' : 'صنف')
                        : suggestion.name,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontWeight: FontWeight.w800,
                      fontSize: 14,
                      color: AppColors.textDark,
                    ),
                  ),
                  const SizedBox(height: 3),
                  Text(
                    subtitle,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      color: AppColors.textMuted,
                      fontSize: 11.5,
                      fontWeight: FontWeight.w600,
                      height: 1.35,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Wrap(
                    spacing: 6,
                    runSpacing: 6,
                    children: [
                      _MetaChip(label: '×${suggestion.quantity}'),
                      _MetaChip(label: suggestion.displayPrice),
                      _MetaChip(
                        label: 'الإجمالي ${suggestion.displayLineTotal}',
                        emphasize: true,
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(width: 8),
          GestureDetector(
            onTap: (busy || disabled) ? null : onAdd,
            behavior: HitTestBehavior.opaque,
            child: Container(
              width: 40,
              height: 40,
              alignment: Alignment.center,
              decoration: BoxDecoration(
                color: (busy || disabled)
                    ? AppColors.fieldFill
                    : AppColors.mint,
                borderRadius: BorderRadius.circular(12),
              ),
              child: busy
                  ? const SizedBox(
                      width: 16,
                      height: 16,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                        color: AppColors.mintDark,
                      ),
                    )
                  : const Icon(
                      Icons.add_rounded,
                      color: Colors.white,
                      size: 22,
                    ),
            ),
          ),
        ],
      ),
    );
  }
}

class _MetaChip extends StatelessWidget {
  const _MetaChip({required this.label, this.emphasize = false});

  final String label;
  final bool emphasize;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: emphasize ? const Color(0xFFE8F7EF) : AppColors.fieldFill,
        borderRadius: BorderRadius.circular(8),
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w800,
          color: emphasize ? AppColors.mintDark : AppColors.textMuted,
        ),
      ),
    );
  }
}

class _Composer extends StatelessWidget {
  const _Composer({required this.controller});

  final AiChatController controller;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.white,
      elevation: 8,
      shadowColor: Colors.black12,
      child: SafeArea(
        top: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(12, 10, 12, 10),
          child: Obx(() {
            final canSend = controller.input.value.trim().isNotEmpty &&
                !controller.sending.value &&
                controller.status.value.enabled;

            return Row(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Expanded(
                  child: TextField(
                    controller: controller.textController,
                    onChanged: controller.onInputChanged,
                    enabled: controller.status.value.enabled &&
                        !controller.sending.value,
                    minLines: 1,
                    maxLines: 4,
                    textInputAction: TextInputAction.send,
                    onSubmitted: (_) {
                      if (canSend) controller.send();
                    },
                    decoration: InputDecoration(
                      hintText: 'اكتب طلبك... نسأل عن الناقص خطوة بخطوة',
                      filled: true,
                      fillColor: const Color(0xFFF3F7F4),
                      contentPadding: const EdgeInsets.symmetric(
                        horizontal: 16,
                        vertical: 12,
                      ),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(22),
                        borderSide: const BorderSide(color: Color(0xFFD9E8DF)),
                      ),
                      enabledBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(22),
                        borderSide: const BorderSide(color: Color(0xFFD9E8DF)),
                      ),
                      focusedBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(22),
                        borderSide: const BorderSide(
                          color: AppColors.mint,
                          width: 1.4,
                        ),
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                Material(
                  color: canSend ? AppColors.mintDark : AppColors.fieldFill,
                  shape: const CircleBorder(),
                  child: InkWell(
                    customBorder: const CircleBorder(),
                    onTap: canSend ? controller.send : null,
                    child: SizedBox(
                      width: 48,
                      height: 48,
                      child: Icon(
                        Icons.send_rounded,
                        color: canSend ? Colors.white : AppColors.textHint,
                      ),
                    ),
                  ),
                ),
              ],
            );
          }),
        ),
      ),
    );
  }
}
