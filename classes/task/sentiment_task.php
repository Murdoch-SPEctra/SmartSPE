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
 * Sentiment analysis scheduled task for SmartSPE module.
 *
 * @package mod_smartspe
 * @copyright 2025 SPEctra
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_smartspe\task;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../../locallib.php');

class sentiment_task extends \core\task\scheduled_task {

    public function get_name(): string {
        return get_string('sentiment_task', 'mod_smartspe');
    }

    public function execute(): void {
        global $DB;

        $MAX_COMMENTS = 50;
        $MAX_BATCH_SIZE = 5;
        mtrace("=== Starting SmartSPE Sentiment Analysis Task ===");

        try {
            // Fetch comments where sentiment is null AND submission has a submitted_at value
            $sql = "
                SELECT c.id,c.comment
                FROM {smartspe_comment} c
                JOIN {smartspe_submission} s ON c.submission_id = s.id
                WHERE c.sentiment IS NULL
                AND s.submitted_at IS NOT NULL
            ";
            $comments = $DB->get_records_sql($sql);
            $comments_count = count($comments);
            if($comments_count > 0){
                mtrace("Found {$comments_count} comments needing sentiment analysis.");
            } else {
                mtrace("No comments found needing sentiment analysis. Task complete.");
                return;
            }
            $count = 0;

            $commentsArray = array_map(function($comment) {
                return $comment->comment;
            }, $comments);
            for ($i = 0; $i < count($commentsArray); $i += $MAX_BATCH_SIZE) {
                // Output comments object to mtrace
                $batch = array_slice($commentsArray, $i, $MAX_BATCH_SIZE , true);              
                $sentiments = smartspe_get_sentiment_batch($batch);
                foreach ($batch as $id => $comment) {
                    if (isset($sentiments[$id])) {
                        $update = (object)[
                            'id' => $id,
                            'sentiment' => $sentiments[$id] ?? throw new \Exception("No sentiment returned")
                        ];
                        $DB->update_record('smartspe_comment', $update);
                        $count++;
                    } else {
                        mtrace("Warning: no sentiment returned for comment ID {$id}");
                    }
                }
                if ($count >= $MAX_COMMENTS) {
                    break; 
                }
            }

            mtrace("=== Finished SmartSPE Sentiment Analysis Task {$count} comments processed ===");
        } catch (\Exception $e) {
            mtrace("Error occurred during sentiment analysis task: " . $e->getMessage());
        }
    }
}
