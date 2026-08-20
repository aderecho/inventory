<?php

namespace App\Services;

use App\Models\AssetCondition;
use Illuminate\Database\Eloquent\Collection;

class AssetConditionService
{
    public function getAllConditions(): Collection
    {
        return AssetCondition::orderBy('condition_name')
            ->get();
    }

    public function getConditionById(int $id): ?AssetCondition
    {
        return AssetCondition::find($id);
    }

    public function getConditionByName(string $name): ?AssetCondition
    {
        return AssetCondition::where('condition_name', $name)->first();
    }

    public function createCondition(
        string $conditionName,
        ?string $description = null
    ): AssetCondition {
        return AssetCondition::create([
            'condition_name' => $conditionName,
            'description' => $description,
        ]);
    }

    public function updateCondition(
        int $id,
        array $data
    ): AssetCondition {
        $condition = AssetCondition::findOrFail($id);
        $condition->update($data);

        return $condition->fresh();
    }

    public function deleteCondition(int $id): bool
    {
        return AssetCondition::destroy($id) > 0;
    }

    public function getConditionWithStats(): Collection
    {
        return AssetCondition::withCount('inspections')
            ->orderBy('condition_name')
            ->get();
    }

    public function searchConditions(string $query): Collection
    {
        return AssetCondition::where('condition_name', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->orderBy('condition_name')
            ->get();
    }
}
