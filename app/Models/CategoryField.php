<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryField extends Model
{
    use HasTranslations, HasUuids;

    protected array $translatable = ['label', 'help'];

    protected $fillable = [
        'category_id', 'key', 'label', 'help', 'data_type', 'unit', 'required',
        'filterable', 'options', 'min_value', 'max_value', 'display_order',
    ];

    protected function casts(): array
    {
        return [
            'label' => 'array',
            'help' => 'array',
            'options' => 'array',
            'required' => 'boolean',
            'filterable' => 'boolean',
            'min_value' => 'float',
            'max_value' => 'float',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function name(): string
    {
        return $this->translate('label') ?? $this->key;
    }

    /** Laravel validation rules derived from the field definition. */
    public function validationRules(): array
    {
        $rules = [$this->required ? 'required' : 'nullable'];

        $rules[] = match ($this->data_type) {
            'number' => 'numeric',
            'integer' => 'integer',
            'boolean' => 'boolean',
            'date' => 'date',
            'multiselect' => 'array',
            default => 'string',
        };

        if ($this->min_value !== null && in_array($this->data_type, ['number', 'integer'], true)) {
            $rules[] = 'min:'.$this->min_value;
        }

        if ($this->max_value !== null && in_array($this->data_type, ['number', 'integer'], true)) {
            $rules[] = 'max:'.$this->max_value;
        }

        if ($this->data_type === 'select' && is_array($this->options)) {
            $rules[] = 'in:'.implode(',', array_keys($this->options));
        }

        return $rules;
    }
}
