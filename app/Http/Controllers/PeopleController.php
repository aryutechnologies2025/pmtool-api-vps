<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\People;
use App\Models\EntryProcessModel;

class PeopleController extends Controller
{
    public function index()
    {
        // Fetch all records from the People model
        $totalProjects = People::with(['createdByUser'])
            ->where('position', '!=', 'Admin')
            ->where('position', '!=', '13')
            ->where('position', '!=', '14')
            ->get();

        // Loop through each person and count based on their position
        foreach ($totalProjects as $entry) {
            // Get the employee's position
            $emp_pos = $entry->position;
            $emp_id = $entry->id;

            // Initialize count variables for each role
            $writerCount = 0;
            $reviewerCount = 0;
            $journalCount = 0;
            $statisticanCount = 0;
            $writerPendingCount = 0;
            $reviewerPendingCount = 0;
            $statisticanPendingCount = 0;
            $journalPendingCount = 0;

            // Check based on the position and get the count for related entries in EntryProcessModel
            if ($emp_pos == 7) {
                // If the position is 7, check the writer column
                $writerCount = EntryProcessModel::where('writer', $emp_id)->count();
                $writerPendingCount = EntryProcessModel::where('writer', $emp_id)->where('process_status', '!=', 'completed')->count();
            } elseif ($emp_pos == 8) {
                // If the position is 8, check the reviewer column
                $reviewerCount = EntryProcessModel::where('reviewer', $emp_id)->count();
                $reviewerPendingCount = EntryProcessModel::where('reviewer', $emp_id)->where('process_status', '!=', 'completed')->count();
            } elseif ($emp_pos == 10) {
                // If the position is 10, check the journal column
                $journalCount = EntryProcessModel::where('journal', $emp_id)->count();
                $journalPendingCount = EntryProcessModel::where('journal', $emp_id)->where('process_status', '!=', 'completed')->count();

            } elseif ($emp_pos == 11) {
                // If the position is 11, check the statistican column
                $statisticanCount = EntryProcessModel::where('statistican', $emp_id)->count();
                $statisticanPendingCount = EntryProcessModel::where('statistican', $emp_id)->where('process_status', '!=', 'completed')->count();

            }

            // Add the counts to the person's data for response
            $entry->writer_count = $writerCount;
            $entry->reviewer_count = $reviewerCount;
            $entry->journal_count = $journalCount;
            $entry->statistican_count = $statisticanCount;
            $entry->writerPendingCount = $writerPendingCount;
            $entry->reviewerPendingCount = $reviewerPendingCount;
            $entry->statisticanPendingCount = $statisticanPendingCount;
            $entry->journalPendingCount = $journalPendingCount;
        }

        // Return the data as a JSON response with counts
        return response()->json(['details' => $totalProjects]);
    }
}
