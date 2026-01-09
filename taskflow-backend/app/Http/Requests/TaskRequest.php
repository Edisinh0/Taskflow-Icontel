<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Auth middleware verificará autenticación
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Información básica de la tarea
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'priority' => 'required|in:High,Medium,Low',
            'status' => 'nullable|in:Not Started,In Progress,Completed,Pending Input,Deferred',

            // Fechas (requeridas para SuiteCRM v4.1)
            'date_start' => 'required|date_format:Y-m-d H:i:s|before_or_equal:date_due',
            'date_due' => 'required|date_format:Y-m-d H:i:s|after_or_equal:date_start',

            // Relaciones con Case/Opportunity (requeridas)
            'parent_type' => 'required|in:Cases,Opportunities',
            'parent_id' => 'required|string|max:36',

            // Campos opcionales
            'assigned_user_id' => 'nullable|integer|exists:users,id',
            'sweetcrm_assigned_user_id' => 'nullable|string|max:36',
            'completion_percentage' => 'nullable|integer|min:0|max:100',
            'flow_id' => 'nullable|integer|exists:flows,id',
            'parent_task_id' => 'nullable|integer|exists:tasks,id',
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'title.required' => 'El título de la tarea es requerido',
            'title.max' => 'El título no puede exceder 255 caracteres',
            'priority.required' => 'La prioridad es requerida',
            'priority.in' => 'La prioridad debe ser High, Medium o Low',
            'date_start.required' => 'La fecha de inicio es requerida',
            'date_start.date_format' => 'La fecha de inicio debe tener formato Y-m-d H:i:s',
            'date_start.before_or_equal' => 'La fecha de inicio debe ser anterior o igual a la fecha de término',
            'date_due.required' => 'La fecha de término es requerida',
            'date_due.date_format' => 'La fecha de término debe tener formato Y-m-d H:i:s',
            'date_due.after_or_equal' => 'La fecha de término debe ser posterior o igual a la fecha de inicio',
            'parent_type.required' => 'El tipo de padre (Cases/Opportunities) es requerido',
            'parent_type.in' => 'El tipo de padre debe ser Cases u Opportunities',
            'parent_id.required' => 'El ID del padre es requerido',
        ];
    }

    /**
     * Transform the input before validation
     */
    protected function prepareForValidation(): void
    {
        // Si las fechas vienen en formato ISO, convertirlas a Y-m-d H:i:s
        if ($this->has('date_start') && $this->date_start) {
            $this->merge([
                'date_start' => $this->formatDateForSuiteCRM($this->date_start),
            ]);
        }

        if ($this->has('date_due') && $this->date_due) {
            $this->merge([
                'date_due' => $this->formatDateForSuiteCRM($this->date_due),
            ]);
        }

        // Default status
        if (!$this->has('status') || !$this->status) {
            $this->merge(['status' => 'Not Started']);
        }
    }

    /**
     * Convertir formato ISO datetime-local a Y-m-d H:i:s
     */
    private function formatDateForSuiteCRM(string $dateString): string
    {
        try {
            // Soporta formatos: 2026-01-09T14:30, 2026-01-09 14:30, etc.
            $date = \DateTime::createFromFormat('Y-m-d\TH:i', $dateString) 
                 ?? \DateTime::createFromFormat('Y-m-d H:i', $dateString)
                 ?? \DateTime::createFromFormat('Y-m-d H:i:s', $dateString);

            if (!$date) {
                $date = new \DateTime($dateString);
            }

            return $date->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            // Si no se puede parsear, devolver tal cual (será rechazado por validación)
            return $dateString;
        }
    }
}
