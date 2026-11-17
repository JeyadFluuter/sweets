import 'package:sweet_store/core/constants/api_endpoints.dart';
import 'package:sweet_store/core/utils/json_parsers.dart';

class AiSuggestionModel {
  const AiSuggestionModel({
    required this.type,
    required this.id,
    required this.name,
    this.quantity = 1,
    this.unitPrice,
    this.imageUrl,
    this.reason,
  });

  final String type;
  final int id;
  final String name;
  final int quantity;
  final num? unitPrice;
  final String? imageUrl;
  final String? reason;

  bool get isProduct => type == 'product';
  bool get isCollection => type == 'collection';

  num get lineTotal {
    final value = (unitPrice ?? 0) * quantity;
    return value;
  }

  String get displayPrice {
    final value = unitPrice ?? 0;
    return _money(value);
  }

  String get displayLineTotal => _money(lineTotal);

  static String _money(num value) {
    final fixed =
        value % 1 == 0 ? value.toStringAsFixed(0) : value.toStringAsFixed(2);
    return '$fixed د.ل';
  }

  factory AiSuggestionModel.fromJson(Map<String, dynamic> json) {
    return AiSuggestionModel(
      type: asString(json['type']) ?? 'product',
      id: asInt(json['id']) ?? 0,
      name: asString(json['name']) ?? '',
      quantity: asInt(json['quantity']) ?? 1,
      unitPrice: asNum(json['unit_price']),
      imageUrl: ApiEndpoints.resolveMediaUrl(asString(json['image_url'])),
      reason: asString(json['reason']),
    );
  }
}

enum AiReplyStatus {
  collectingInfo,
  proposalReady,
  modifyingProposal,
  outOfScope,
  unknown;

  static AiReplyStatus fromWire(String? value) {
    switch (value) {
      case 'collecting_info':
        return AiReplyStatus.collectingInfo;
      case 'proposal_ready':
        return AiReplyStatus.proposalReady;
      case 'modifying_proposal':
        return AiReplyStatus.modifyingProposal;
      case 'out_of_scope':
        return AiReplyStatus.outOfScope;
      default:
        return AiReplyStatus.unknown;
    }
  }

  bool get showsOffer =>
      this == AiReplyStatus.proposalReady ||
      this == AiReplyStatus.modifyingProposal;
}

class AiChatMessageModel {
  AiChatMessageModel({
    required this.role,
    required this.content,
    this.suggestions = const [],
    this.status = AiReplyStatus.unknown,
    this.estimatedTotal,
    this.isTyping = false,
    DateTime? createdAt,
  }) : createdAt = createdAt ?? DateTime.now();

  final String role;
  final String content;
  final List<AiSuggestionModel> suggestions;
  final AiReplyStatus status;
  final num? estimatedTotal;
  final bool isTyping;
  final DateTime createdAt;

  bool get isUser => role == 'user';
  bool get isAssistant => role == 'assistant';

  AiChatMessageModel copyWith({
    String? role,
    String? content,
    List<AiSuggestionModel>? suggestions,
    AiReplyStatus? status,
    num? estimatedTotal,
    bool? isTyping,
  }) {
    return AiChatMessageModel(
      role: role ?? this.role,
      content: content ?? this.content,
      suggestions: suggestions ?? this.suggestions,
      status: status ?? this.status,
      estimatedTotal: estimatedTotal ?? this.estimatedTotal,
      isTyping: isTyping ?? this.isTyping,
      createdAt: createdAt,
    );
  }
}

class AiChatReplyModel {
  const AiChatReplyModel({
    required this.conversationId,
    required this.message,
    this.suggestions = const [],
    this.status = AiReplyStatus.unknown,
    this.estimatedTotal,
  });

  final int conversationId;
  final String message;
  final List<AiSuggestionModel> suggestions;
  final AiReplyStatus status;
  final num? estimatedTotal;

  factory AiChatReplyModel.fromJson(Map<String, dynamic> json) {
    final raw = json['suggestions'] ?? json['suggested_items'];
    final suggestions = <AiSuggestionModel>[];
    if (raw is List) {
      for (final item in raw) {
        if (item is Map) {
          final suggestion = AiSuggestionModel.fromJson(
            Map<String, dynamic>.from(item),
          );
          if (suggestion.id > 0) {
            suggestions.add(suggestion);
          }
        }
      }
    }

    return AiChatReplyModel(
      conversationId: asInt(json['conversation_id']) ?? 0,
      message: _displayMessage(asString(json['message']) ?? ''),
      suggestions: suggestions,
      status: AiReplyStatus.fromWire(asString(json['status'])),
      estimatedTotal: asNum(json['estimated_total']),
    );
  }

  static String _displayMessage(String raw) {
    final trimmed = raw.trim();
    if (trimmed.isEmpty) return trimmed;

    final messageMatch = RegExp(
      r'"message"\s*:\s*"((?:\\.|[^"\\])*)',
      dotAll: true,
    ).firstMatch(trimmed);
    if (messageMatch != null) {
      final extracted = messageMatch.group(1);
      if (extracted != null && extracted.trim().isNotEmpty) {
        return extracted
            .replaceAll(r'\"', '"')
            .replaceAll(r'\n', '\n')
            .trim();
      }
    }

    if (trimmed.startsWith('{') || trimmed.startsWith('[')) {
      return 'تم تجهيز الرد. أعد المحاولة أو صف طلبك بجملة أوضح.';
    }

    return trimmed;
  }
}

class AiStatusModel {
  const AiStatusModel({
    required this.enabled,
    this.connected = false,
    this.ready = false,
    this.provider,
    this.dailyMessageLimit = 30,
  });

  /// مفعّل من الداشبورد → يظهر التبويب في التطبيق
  final bool enabled;

  /// نجح اختبار الاتصال
  final bool connected;

  /// جاهز للمحادثة (مفعّل + متصل)
  final bool ready;

  final String? provider;
  final int dailyMessageLimit;

  factory AiStatusModel.fromJson(Map<String, dynamic> json) {
    final enabled = asBool(json['enabled']);
    final connected = asBool(json['connected']);
    final ready = json.containsKey('ready')
        ? asBool(json['ready'])
        : (enabled && connected);

    return AiStatusModel(
      enabled: enabled,
      connected: connected,
      ready: ready,
      provider: asString(json['provider']),
      dailyMessageLimit: asInt(json['daily_message_limit']) ?? 30,
    );
  }

  factory AiStatusModel.disabled() => const AiStatusModel(enabled: false);
}
