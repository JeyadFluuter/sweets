import 'package:sweet_store/core/constants/api_endpoints.dart';
import 'package:sweet_store/core/network/api_client.dart';
import 'package:sweet_store/models/ai_chat_model.dart';

class AiService {
  AiService(this._api);

  final ApiClient _api;

  Future<AiStatusModel> fetchStatus() async {
    final body = await _api.get(ApiEndpoints.aiStatus);
    return AiStatusModel.fromJson(
      Map<String, dynamic>.from(body['data'] as Map),
    );
  }

  Future<AiChatReplyModel> chat({
    required String message,
    int? conversationId,
  }) async {
    final body = await _api.post(
      ApiEndpoints.aiChat,
      body: {
        'message': message,
        'conversation_id': ?conversationId,
      },
    );
    return AiChatReplyModel.fromJson(
      Map<String, dynamic>.from(body['data'] as Map),
    );
  }
}
