<?php

namespace App\Services;

use App\Models\QuizQuestion;

class QuizGrader
{
    public static function check(QuizQuestion $question, mixed $answer): ?bool
    {
        $type = $question->question_type ?: 'single';
        if ($type === 'open') {
            if (!is_string($answer) || trim($answer) === '' || mb_strlen($answer) > 10000) throw new \DomainException('Answer every open question.');
            return null;
        }
        if ($type === 'user_input') {
            if (!is_string($answer) || trim($answer) === '') throw new \DomainException('Answer every user-input question.');
            return collect($question->accepted_answers ?? [])->contains(fn ($accepted) => mb_strtolower(trim($accepted)) === mb_strtolower(trim($answer)));
        }
        $options = $question->options ?? [];
        if ($type === 'multiple') {
            if (!is_array($answer) || !$answer) throw new \DomainException('Select at least one answer.');
            $selected = array_map(fn ($value) => filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE), $answer);
            if (in_array(null, $selected, true) || count(array_unique($selected)) !== count($selected)
                || collect($selected)->contains(fn ($value) => $value < 0 || $value >= count($options))) throw new \DomainException('Invalid multiple-choice answer.');
            $correct = array_map('intval', $question->correct_answers ?? []);
            sort($selected); sort($correct);
            return $selected === $correct;
        }
        $choice = filter_var($answer, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
        if ($choice === null || $choice < 0 || $choice >= count($options)) throw new \DomainException('Invalid single-choice answer.');
        return (string) $choice === $question->correct_answer;
    }

    public static function score($questions, array $answers, array $manualResults = [], ?\App\Models\Quiz $quiz = null): int
    {
        $earned = 0;
        $possible = max(1, $questions->sum(fn ($question) => $question->marks ?? 1));
        foreach ($questions as $question) {
            if (!array_key_exists($question->id, $answers)) throw new \DomainException('Answer every question.');
            $checked = self::check($question, $answers[$question->id]);
            if ($checked === null) $checked = (bool) ($manualResults[$question->id] ?? false);
            if ($checked) $earned += $question->marks ?? 1;
            elseif ($quiz?->enable_negative_marking) $earned -= $quiz->marks_to_cut ?? 0;
        }
        return (int) round(max(0, $earned) / $possible * 100);
    }
}
