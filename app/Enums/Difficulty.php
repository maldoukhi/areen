<?php

declare(strict_types=1);

namespace App\Enums;

enum Difficulty: string
{
    case Beginner = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced = 'advanced';

    public function label(): string
    {
        return __('exercise.difficulty.'.$this->value);
    }
}
