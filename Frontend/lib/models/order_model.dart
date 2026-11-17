import 'package:sweet_store/core/utils/json_parsers.dart';
import 'package:sweet_store/models/cart_model.dart';

class OrderItemModel {
  const OrderItemModel({
    required this.id,
    required this.name,
    required this.quantity,
    required this.unitPrice,
    required this.lineTotal,
    this.itemType,
    this.itemId,
  });

  final int id;
  final String name;
  final int quantity;
  final num unitPrice;
  final num lineTotal;
  final String? itemType;
  final int? itemId;

  factory OrderItemModel.fromJson(Map<String, dynamic> json) {
    return OrderItemModel(
      id: asInt(json['id']) ?? 0,
      name: asString(json['name']) ?? '',
      quantity: asInt(json['quantity']) ?? 1,
      unitPrice: asNum(json['unit_price']) ?? 0,
      lineTotal: asNum(json['line_total']) ?? 0,
      itemType: asString(json['item_type']),
      itemId: asInt(json['item_id']),
    );
  }
}

class OrderTimelineEvent {
  const OrderTimelineEvent({
    this.from,
    this.to,
    this.reason,
    this.at,
  });

  final String? from;
  final String? to;
  final String? reason;
  final String? at;

  factory OrderTimelineEvent.fromJson(Map<String, dynamic> json) {
    return OrderTimelineEvent(
      from: asString(json['from']),
      to: asString(json['to']),
      reason: asString(json['reason']),
      at: asString(json['at']),
    );
  }
}

class PaymentModel {
  const PaymentModel({
    required this.id,
    required this.orderId,
    required this.amount,
    this.type,
    this.method,
    this.status,
    this.reference,
    this.gateway,
    this.meta,
    this.paidAt,
  });

  final int id;
  final int orderId;
  final num amount;
  final String? type;
  final String? method;
  final String? status;
  final String? reference;
  final String? gateway;
  final Map<String, dynamic>? meta;
  final String? paidAt;

  String get displayAmount => moneyLabel(amount, 'LYD');

  factory PaymentModel.fromJson(Map<String, dynamic> json) {
    return PaymentModel(
      id: asInt(json['id']) ?? 0,
      orderId: asInt(json['order_id']) ?? 0,
      amount: asNum(json['amount']) ?? 0,
      type: asString(json['type']),
      method: asString(json['method']),
      status: asString(json['status']),
      reference: asString(json['reference']),
      gateway: asString(json['gateway']),
      meta: json['meta'] is Map
          ? Map<String, dynamic>.from(json['meta'] as Map)
          : null,
      paidAt: asString(json['paid_at']),
    );
  }
}

class OrderModel {
  const OrderModel({
    required this.id,
    this.number,
    this.status,
    this.statusLabel,
    this.deliveryType,
    this.fulfillmentDate,
    this.fulfillmentTime,
    this.addressText,
    this.lat,
    this.lng,
    this.deliveryFee = 0,
    this.recipientIsSelf = true,
    this.recipientName,
    this.recipientPhone,
    this.subtotal = 0,
    this.discount = 0,
    this.total = 0,
    this.paymentPlan,
    this.paidAmount = 0,
    this.remainingAmount = 0,
    this.paymentStatus,
    this.paymentDueAt,
    this.customerNote,
    this.rejectionReason,
    this.items = const [],
    this.payments = const [],
    this.timeline = const [],
    this.createdAt,
    this.currency = 'LYD',
  });

  final int id;
  final String? number;
  final String? status;
  final String? statusLabel;
  final String? deliveryType;
  final String? fulfillmentDate;
  final String? fulfillmentTime;
  final String? addressText;
  final num? lat;
  final num? lng;
  final num deliveryFee;
  final bool recipientIsSelf;
  final String? recipientName;
  final String? recipientPhone;
  final num subtotal;
  final num discount;
  final num total;
  final int? paymentPlan;
  final num paidAmount;
  final num remainingAmount;
  final String? paymentStatus;
  final String? paymentDueAt;
  final String? customerNote;
  final String? rejectionReason;
  final List<OrderItemModel> items;
  final List<PaymentModel> payments;
  final List<OrderTimelineEvent> timeline;
  final String? createdAt;
  final String currency;

  bool get canPay => status == 'awaiting_payment';
  bool get canCancel =>
      status == 'pending_review' ||
      status == 'awaiting_payment' ||
      status == 'confirmed';

  String get displayTotal => moneyLabel(total, currency);
  String get displaySubtotal => moneyLabel(subtotal, currency);
  String get displayDiscount => moneyLabel(discount, currency);
  String get displayDeliveryFee => moneyLabel(deliveryFee, currency);
  String get displayRemaining => moneyLabel(remainingAmount, currency);

  String get deliveryTypeLabel =>
      deliveryType == 'delivery' ? 'توصيل' : 'استلام من المعمل';

  String get fulfillmentLabel {
    final date = fulfillmentDate ?? '';
    final time = fulfillmentTime ?? '';
    if (date.isEmpty) return '—';
    return time.isEmpty ? date : '$date · $time';
  }

  factory OrderModel.fromJson(Map<String, dynamic> json) {
    return OrderModel(
      id: asInt(json['id']) ?? 0,
      number: asString(json['number']),
      status: asString(json['status']),
      statusLabel: asString(json['status_label']),
      deliveryType: asString(json['delivery_type']),
      fulfillmentDate: asString(json['fulfillment_date']),
      fulfillmentTime: asString(json['fulfillment_time']),
      addressText: asString(json['address_text']),
      lat: asNum(json['lat']),
      lng: asNum(json['lng']),
      deliveryFee: asNum(json['delivery_fee']) ?? 0,
      recipientIsSelf: asBool(json['recipient_is_self'], fallback: true),
      recipientName: asString(json['recipient_name']),
      recipientPhone: asString(json['recipient_phone']),
      subtotal: asNum(json['subtotal']) ?? 0,
      discount: asNum(json['discount']) ?? 0,
      total: asNum(json['total']) ?? 0,
      paymentPlan: asInt(json['payment_plan']),
      paidAmount: asNum(json['paid_amount']) ?? 0,
      remainingAmount: asNum(json['remaining_amount']) ?? 0,
      paymentStatus: asString(json['payment_status']),
      paymentDueAt: asString(json['payment_due_at']),
      customerNote: asString(json['customer_note']),
      rejectionReason: asString(json['rejection_reason']),
      items: (json['items'] as List? ?? [])
          .whereType<Map>()
          .map((e) => OrderItemModel.fromJson(Map<String, dynamic>.from(e)))
          .toList(),
      payments: (json['payments'] as List? ?? [])
          .whereType<Map>()
          .map((e) => PaymentModel.fromJson(Map<String, dynamic>.from(e)))
          .toList(),
      timeline: (json['timeline'] as List? ?? [])
          .whereType<Map>()
          .map(
            (e) => OrderTimelineEvent.fromJson(Map<String, dynamic>.from(e)),
          )
          .toList(),
      createdAt: asString(json['created_at']),
    );
  }
}

class FulfillmentSlot {
  const FulfillmentSlot({
    required this.start,
    required this.end,
    required this.remaining,
    required this.slotId,
  });

  final String start;
  final String end;
  final int remaining;
  final int slotId;

  /// يُرسل لـ POST /orders كـ fulfillment_time بصيغة H:i
  String get timeForOrder {
    final raw = start.trim();
    if (raw.length >= 5) return raw.substring(0, 5);
    return raw;
  }

  String get label => '$timeForOrder — ${end.length >= 5 ? end.substring(0, 5) : end}';

  factory FulfillmentSlot.fromJson(Map<String, dynamic> json) {
    return FulfillmentSlot(
      start: asString(json['start']) ?? '',
      end: asString(json['end']) ?? '',
      remaining: asInt(json['remaining']) ?? 0,
      slotId: asInt(json['slot_id']) ?? 0,
    );
  }
}
