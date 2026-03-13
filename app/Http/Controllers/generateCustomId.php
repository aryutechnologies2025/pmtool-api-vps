<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EntryProcessModel;
class generateCustomId extends Controller
{
    public function generateCustomId(Request $request)
{
    $request->validate([
        'type_of_work' => 'required|string',
    ]);

    $typeOfWork = $request->type_of_work;
    $customId = '';

    $lastEntry = EntryProcessModel::where('type_of_work', $typeOfWork)
        // ->where('is_deleted', 0)
        ->orderBy('project_id', 'desc')
        ->latest()
        ->first();

    $increment = $lastEntry ? (int)substr($lastEntry->project_id, strlen($typeOfWork) + 1) + 1 : 1;
    $formattedIncrement = str_pad($increment, 3, '0', STR_PAD_LEFT);
    $customId = $typeOfWork . '-' . $formattedIncrement;

    return response()->json([
        'custom_id' => $customId,
    ]);
}

}
