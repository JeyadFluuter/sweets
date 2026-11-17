import 'dart:async';

import 'package:flutter/widgets.dart';
import 'package:get/get.dart';
import 'package:sweet_store/models/ai_chat_model.dart';
import 'package:sweet_store/services/ai_service.dart';

/// يتحكم في ظهور تبويب المساعد حسب تفعيل الداشبورد.
class AiGateController extends GetxController with WidgetsBindingObserver {
  AiGateController(this._ai);

  final AiService _ai;

  final status = AiStatusModel.disabled().obs;
  final loading = true.obs;

  Timer? _pollTimer;

  bool get isVisible => status.value.enabled;

  @override
  void onInit() {
    super.onInit();
    WidgetsBinding.instance.addObserver(this);
    refreshStatus();
    _pollTimer = Timer.periodic(const Duration(seconds: 20), (_) {
      refreshStatus(silent: true);
    });
  }

  @override
  void onClose() {
    _pollTimer?.cancel();
    WidgetsBinding.instance.removeObserver(this);
    super.onClose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      refreshStatus(silent: true);
    }
  }

  Future<void> refreshStatus({bool silent = false}) async {
    if (!silent) loading.value = true;
    try {
      status.value = await _ai.fetchStatus();
    } catch (_) {
      // عند الريفريش الصامت نحافظ على آخر حالة معروفة بدل إخفاء التبويب فجأة
      if (!silent) {
        status.value = AiStatusModel.disabled();
      }
    } finally {
      if (!silent) loading.value = false;
    }
  }
}
