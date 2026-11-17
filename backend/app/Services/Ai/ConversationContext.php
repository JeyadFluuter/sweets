<?php

namespace App\Services\Ai;

/**
 * سياق المحادثة متعدد المراحل — يُحفظ في ai_conversations.context
 */
class ConversationContext
{
    public const STATUS_COLLECTING = 'collecting_info';

    public const STATUS_PROPOSAL = 'proposal_ready';

    public const STATUS_MODIFYING = 'modifying_proposal';

    public const STATUS_OUT_OF_SCOPE = 'out_of_scope';

    public function __construct(
        public ?string $occasion = null,
        public ?int $people = null,
        public ?float $budget = null,
        /** @var list<string> */
        public array $focusKeywords = [],
        public ?string $style = null, // fancy|cheap|null
        /** @var list<string> product:1|collection:2 */
        public array $lastSuggestionKeys = [],
        /** @var list<array<string,mixed>> */
        public array $lastSuggestions = [],
        public string $status = self::STATUS_COLLECTING,
        public ?string $lastIntent = null,
    ) {}

    public static function fromArray(?array $data): self
    {
        $data ??= [];

        return new self(
            occasion: isset($data['occasion']) ? (string) $data['occasion'] : null,
            people: isset($data['people']) ? (int) $data['people'] : null,
            budget: isset($data['budget']) ? (float) $data['budget'] : null,
            focusKeywords: array_values(array_filter(array_map('strval', $data['focus_keywords'] ?? []))),
            style: isset($data['style']) ? (string) $data['style'] : null,
            lastSuggestionKeys: array_values(array_filter(array_map('strval', $data['last_suggestion_keys'] ?? []))),
            lastSuggestions: is_array($data['last_suggestions'] ?? null) ? $data['last_suggestions'] : [],
            status: (string) ($data['status'] ?? self::STATUS_COLLECTING),
            lastIntent: isset($data['last_intent']) ? (string) $data['last_intent'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'occasion' => $this->occasion,
            'people' => $this->people,
            'budget' => $this->budget,
            'focus_keywords' => $this->focusKeywords,
            'style' => $this->style,
            'last_suggestion_keys' => $this->lastSuggestionKeys,
            'last_suggestions' => $this->lastSuggestions,
            'status' => $this->status,
            'last_intent' => $this->lastIntent,
        ];
    }

    public function summaryForPrompt(): string
    {
        return json_encode([
            'occasion' => $this->occasion,
            'people' => $this->people,
            'budget' => $this->budget,
            'focus_keywords' => $this->focusKeywords,
            'style' => $this->style,
            'status' => $this->status,
            'last_suggestion_keys' => $this->lastSuggestionKeys,
        ], JSON_UNESCAPED_UNICODE);
    }

    public function hasFocus(): bool
    {
        return $this->focusKeywords !== [];
    }

    public function rememberSuggestions(array $suggestions): void
    {
        $this->lastSuggestions = $suggestions;
        $this->lastSuggestionKeys = [];
        foreach ($suggestions as $item) {
            $type = (string) ($item['type'] ?? 'product');
            $id = (int) ($item['id'] ?? 0);
            if ($id > 0) {
                $this->lastSuggestionKeys[] = $type.':'.$id;
            }
        }
    }
}
