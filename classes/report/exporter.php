<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Exporter class for SmartSPE reports.
 *
 * @package mod_smartspe
 * @copyright 2025 SPEctra
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_smartspe\report;

/**
 * Exporter class for SmartSPE reports.
 *
 * Responsible for generating the CSV data used by teacher/export.php.
 */
class exporter {

    /**
     * Generate the CSV data for a given SPE and group.
     *
     * @param int $speid
     * @param int $groupid
     * @return array
     */
    public static function generate_csv(int $speid, int $groupid): array {
        global $DB;

        $csvdata = [];

        // Fetch group
        $group = $DB->get_record('smartspe_group', ['id' => $groupid, 'spe_id' => $speid]);

        if (!$group) {
            $csvdata[] = ["SMARTSPE Report"];
            $csvdata[] = ["No group found for SPE ID: {$speid}, Group ID: {$groupid}"];
            return $csvdata;
        }

        $csvdata[] = ["SMARTSPE Report for SPE ID: {$speid} - Team: {$group->name}"];
        $csvdata[] = []; // blank row

        // --- Fetch group members ---
        $membersql = "
            SELECT u.id, u.firstname, u.lastname, u.idnumber
            FROM {user} u
            JOIN {smartspe_group_member} gm ON gm.user_id = u.id
            WHERE gm.group_id = :groupid
            ORDER BY u.lastname, u.firstname
        ";
        $members = $DB->get_records_sql($membersql, ['groupid' => $groupid]);

        if (!$members) {
            $csvdata[] = ["Student being assessed", "", "", ""];
            $csvdata[] = ["Assessment criteria", "TEAM #", "Student ID", "Surname", "Given Name"];
            $csvdata[] = ["No members found for this group."];
            return $csvdata;
        }

        // Header rows
        $csvdata[] = ["Student being assessed"];
        $csvdata[] = ["Assessment criteria"];

        // Student details table header
        $csvdata[] = ["TEAM #", "Student ID", "Surname", "Given Name"];

        // Member details
        $teamnumber = property_exists($group, 'teamnumber') ? $group->teamnumber : $group->id;
        foreach ($members as $m) {
           $csvdata[] = [
               $teamnumber,
               isset($m->idnumber) ? $m->idnumber : $m->id,
               $m->lastname,
               $m->firstname
           ];
        }

        $csvdata[] = ['Average of Criteria'];
        $avg_label_index = count($csvdata) - 1;
        $csvdata[] = []; // vertical gap

        // Fetch questions
        $questions = $DB->get_records('smartspe_question', ['spe_id' => $speid], 'sort_order ASC, id ASC');
        if (!$questions) {
            $csvdata[] = ["No questions (assessment criteria) defined for this SPE."];
            return $csvdata;
        }

        $memberlist = array_values($members);
        $questionlist = array_values($questions);

        // Build subtables (one per target/student being assessed)
        $subtables = [];
        foreach ($memberlist as $target) {
            $subtable = [];

            // Row 0: Student name
            $subtable[] = [$target->firstname . ' ' . $target->lastname];

            // Row 1: Question numbers
            $qnums = [];
            for ($q = 0; $q < count($questionlist); $q++) {
                $qnums[] = 'Q' . ($q + 1);
            }
            $qnums[] = 'Average for each';
            $subtable[] = $qnums;

            // Rows: ratings from each rater/student
            foreach ($memberlist as $student) {
                $ratingsql = "
                    SELECT q.id AS qid, a.score
                    FROM {smartspe_answer} a
                    JOIN {smartspe_question} q ON q.id = a.question_id
                    JOIN {smartspe_submission} s ON s.id = a.submission_id
                    WHERE s.spe_id = :speid
                      AND a.target_id = :targetid
                      AND s.student_id = :studentid
                    ORDER BY q.sort_order ASC, q.id ASC
                ";
                $answers = $DB->get_records_sql($ratingsql, [
                    'speid' => $speid,
                    'targetid' => $target->id,
                    'studentid' => $student->id
                ]);

                $rowratings = [];
                if ($answers) {
                    $byqid = [];
                    foreach ($answers as $ans) {
                        $byqid[(int)$ans->qid] = $ans->score;
                    }
                    foreach ($questionlist as $qobj) {
                        $qid = (int)$qobj->id;
                        $rowratings[] = array_key_exists($qid, $byqid) ? $byqid[$qid] : '';
                    }
                } else {
                    $rowratings = array_fill(0, count($questionlist), '');
                }

                // Compute average for this student
                $student_avg = 0;
                $student_count = 0;
                foreach ($rowratings as $val) {
                    if (is_numeric($val)) {
                        $student_avg += (float)$val;
                        $student_count++;
                    }
                }
                $student_avg_value = $student_count ? round($student_avg / $student_count, 2) : '';

                // Append student's ratings plus average
                $subtable[] = array_merge($rowratings, [$student_avg_value]);
            }
            
            // --- NEW CODE: Add average of criteria (numbers only) ---
            $questioncount = count($questionlist);
            $sum = array_fill(0, $questioncount, 0);
            $count = array_fill(0, $questioncount, 0);

            // Loop through rating rows (skip first two: name + question numbers)
            for ($r = 2; $r < count($subtable); $r++) {
                for ($q = 0; $q < $questioncount; $q++) {
                    if (isset($subtable[$r][$q]) && is_numeric($subtable[$r][$q])) {
                        $sum[$q] += (float)$subtable[$r][$q];
                        $count[$q]++;
                    }
                }
            }

            // Compute averages per question
            $averages = [];
            for ($q = 0; $q < $questioncount; $q++) {
                $averages[] = $count[$q] ? round($sum[$q] / $count[$q], 2) : '';
            }

            // Append averages as last row (no label here)
            $subtable[] = array_merge($averages, ['']);
            // --- END NEW CODE ---

            $subtables[] = $subtable;
        }

        //MERGE
        $student_table_width = 0;
        foreach ($csvdata as $row) {
            $student_table_width = max($student_table_width, count($row));
        }

        //CORRECT NAME
        $max_qs = count($questionlist)+1;
        foreach ($subtables as &$t) {
            foreach ($t as &$r) {
                $r = array_pad($r, $max_qs, ''); // ensures each row has same number of columns (one per question)
            }
        }
        unset($t, $r);

        $ratings_block = self::merge_tables_side_by_side($subtables, 1);

        // Separate rows
        $names_row = $ratings_block[0]; // names
        $questions_row = $ratings_block[1]; // question numbers
        $rating_values_rows = array_slice($ratings_block, 2); // actual ratings

        // Add 1-column gap after student table for names and questions
        $names_row = array_merge(array_fill(0, $student_table_width + 1, ''), $names_row);
        $questions_row = array_merge(array_fill(0, $student_table_width + 1, ''), $questions_row);

        // Combine all rows with correct vertical placement
        $shifted_ratings = [];
        $shifted_ratings[2] = $names_row;        // row 3: names
        $shifted_ratings[3] = $questions_row;    // row 4: question numbers
        $shifted_ratings[4] = array_fill(0, count($rating_values_rows[0]), ''); // row 5: empty gap

        // Row 6 onward: rating values
        for ($i = 0; $i < count($rating_values_rows); $i++) {
            $shifted_ratings[5 + $i] = $rating_values_rows[$i];
        }

        // Fill any empty rows above row 3 if necessary
        for ($i = 0; $i < 2; $i++) {
            if (!isset($shifted_ratings[$i])) $shifted_ratings[$i] = [];
        }

        // Determine total rows needed
        $needed_rows = max(count($csvdata), count($shifted_ratings));

        // Pad CSV and rating rows
        for ($i = 0; $i < $needed_rows; $i++) {
            if (!isset($csvdata[$i])) $csvdata[$i] = [];
            if (!isset($shifted_ratings[$i])) $shifted_ratings[$i] = [];
        }

        for ($i = 0; $i < $needed_rows; $i++) {
            if (isset($avg_label_index) && $i === $avg_label_index) {
                // Insert 5-column gap only on the label row so label sits left and averages line up
                $merged_csv[] = array_merge(
                    $csvdata[$i],
                    ['', '', '', '', ''],
                    isset($shifted_ratings[$i]) ? $shifted_ratings[$i] : []
                );
            } elseif ($i >= 5) {
                // rating value rows keep the existing 2-column gap you used before
                $merged_csv[] = array_merge(
                    $csvdata[$i],
                    ['', ''],
                    isset($shifted_ratings[$i]) ? $shifted_ratings[$i] : []
                );
            } else {
                $merged_csv[] = array_merge(
                    $csvdata[$i],
                    isset($shifted_ratings[$i]) ? $shifted_ratings[$i] : []
                );
            }
        }

        $csvdata = $merged_csv;

        // End gap
        $csvdata[] = [];
        $csvdata[] = [];

        return $csvdata;
    }

    /**
     * Merge 2D tables horizontally with gaps.
     *
     * @param array $tables
     * @param int $gap
     * @return array
     */
    private static function merge_tables_side_by_side(array $tables, int $gap = 1): array {
        $merged = [];
        if (empty($tables)) return $merged;

        $heights = array_map('count', $tables);
        $maxrows = max($heights);

        $widths = [];
        foreach ($tables as $ti => $table) {
            $maxw = 0;
            foreach ($table as $r) {
                $maxw = max($maxw, count($r));
            }
            $widths[$ti] = max(1, $maxw);
        }

        for ($r = 0; $r < $maxrows; $r++) {
            $row = [];
            foreach ($tables as $ti => $table) {
                $row = array_merge($row, $table[$r] ?? array_fill(0, $widths[$ti], ''));
                if ($ti < count($tables) - 1) {
                    $row = array_merge($row, array_fill(0, $gap, ''));
                }
            }
            $merged[] = $row;
        }

        return $merged;
    }

}
