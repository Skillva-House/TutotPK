<?php
session_start();
include_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../validation.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'tutor') {
    header('Location: ../login.php');
    exit();
}

$page_title  = 'Create Schedule';
$role        = 'tutor';
$user_name   = $_SESSION['name'] ?? 'Tutor User';
$active_page = 'create_schedule';
$tutor_id    = (int) $_SESSION['id'];

$message = '';
$error   = '';

$allowed_repeat_types = ['once', 'daily', 'weekly_1', 'weekly_2', 'weekly_3',
                          'weekly_4', 'weekly_5', 'weekly_6', 'weekly_7'];
$allowed_weekdays = ['1', '2', '3', '4', '5', '6', '7'];

$weekday_labels = [
    '1' => 'Mon',
    '2' => 'Tue',
    '3' => 'Wed',
    '4' => 'Thu',
    '5' => 'Fri',
    '6' => 'Sat',
    '7' => 'Sun',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_schedule'])) {
    $delete_id = (int) ($_POST['delete_schedule_id'] ?? 0);
    if ($delete_id > 0) {
        $stmt = $conn->prepare("DELETE FROM tutor_schedule WHERE id = ? AND tutor_id = ?");
        $stmt->bind_param('ii', $delete_id, $tutor_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $message = 'Schedule deleted successfully.';
        } else {
            $error = 'Could not delete schedule.';
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_schedule'])) {
    $edit_id      = (int) ($_POST['edit_schedule_id'] ?? 0);
    $is_edit      = $edit_id > 0;

    $subject_name = trim($_POST['subject_name'] ?? '');
    $topic_name   = trim($_POST['topic_name']   ?? '');
    $class_date   = trim($_POST['class_date']   ?? '');
    $class_time   = trim($_POST['class_time']   ?? '');
    $repeat_type  = trim($_POST['repeat_type']  ?? 'once');
    $repeat_until = trim($_POST['repeat_until'] ?? '');
    $selected_weekdays = $_POST['weekdays'] ?? [];
    
    $price_1      = (float) ($_POST['price_1']  ?? 2.00);
    $price_2      = (float) ($_POST['price_2']  ?? 4.00);
    $price_5      = (float) ($_POST['price_5']  ?? 8.00);
    $price_12     = (float) ($_POST['price_12'] ?? 20.00);

    if (!is_array($selected_weekdays)) {
        $selected_weekdays = [];
    }

    $selected_weekdays = array_values(array_unique(array_filter($selected_weekdays, function ($day) use ($allowed_weekdays) {
        return in_array((string) $day, $allowed_weekdays, true);
    })));

    $weekly_days_count = 0;
    if (preg_match('/^weekly_([1-7])$/', $repeat_type, $matches) === 1) {
        $weekly_days_count = (int) $matches[1];
    }

    if ($subject_name === '' || $class_date === '' || $class_time === '') {
        $error = 'Subject, date, and time are required.';
    } elseif (!in_array($repeat_type, $allowed_repeat_types, true)) {
        $error = 'Invalid repeat option selected.';
    } else {
        $start_date_obj = DateTime::createFromFormat('Y-m-d', $class_date);

        $today = date('Y-m-d');

        if (!$start_date_obj || $start_date_obj->format('Y-m-d') !== $class_date) {
            $error = 'Invalid class date.';
        } elseif ($class_date < $today) {
            $error = 'Class date cannot be in the past.';
        } else {
            // Validate repeat-until: if provided, ensure it's a valid date and not before the class date.
            // For recurring schedules it is required; for one-time it's optional but still validated if submitted.
            if ($repeat_type !== 'once') {
                $end_date_obj = DateTime::createFromFormat('Y-m-d', $repeat_until);
                if ($repeat_until === '' || !$end_date_obj || $end_date_obj->format('Y-m-d') !== $repeat_until) {
                    $error = 'Please provide a valid Repeat Until date.';
                } elseif ($repeat_until < $class_date) {
                    $error = 'Repeat Until date must be on or after the class date.';
                } elseif ($weekly_days_count > 0 && count($selected_weekdays) !== $weekly_days_count) {
                    $error = 'Please select exactly ' . $weekly_days_count . ' day(s) for this weekly option.';
                }
            } else {
                // If a repeat_until was submitted for a one-time class, validate it (must be a proper date and not before class_date).
                if ($repeat_until !== '') {
                    $end_date_obj_any = DateTime::createFromFormat('Y-m-d', $repeat_until);
                    if (!$end_date_obj_any || $end_date_obj_any->format('Y-m-d') !== $repeat_until) {
                        $error = 'Please provide a valid Repeat Until date.';
                    } elseif ($repeat_until < $class_date) {
                        $error = 'Repeat Until date must be on or after the class date.';
                    }
                }
            }

            if ($error === '') {
                $repeat_days_per_week = $weekly_days_count;
                $db_repeat_until      = ($repeat_type !== 'once' && $repeat_until !== '') ? $repeat_until : null;
                $selected_days_str    = implode(',', $selected_weekdays);

                if ($is_edit) {
                    $stmt = $conn->prepare(
                        "UPDATE tutor_schedule
                         SET subject_name = ?, topic_name = ?, class_date = ?, class_time = ?,
                             repeat_type = ?, repeat_days_per_week = ?, selected_days = ?, repeat_until = ?,
                             price_1 = ?, price_2 = ?, price_5 = ?, price_12 = ?
                         WHERE id = ? AND tutor_id = ?"
                    );
                    $stmt->bind_param(
                        'sssssisssddii',
                        $subject_name, $topic_name, $class_date, $class_time,
                        $repeat_type, $repeat_days_per_week, $selected_days_str, $db_repeat_until,
                        $price_1, $price_2, $price_5, $price_12,
                        $edit_id, $tutor_id
                    );
                    if ($stmt->execute()) {
                        $message = 'Schedule updated successfully.';
                    } else {
                        $error = 'Could not update schedule.';
                    }
                    $stmt->close();
                } else {
                    // Always insert exactly ONE row per schedule submission
                    $stmt = $conn->prepare(
                        "INSERT INTO tutor_schedule
                         (tutor_id, subject_name, topic_name, class_date, class_time,
                          repeat_type, repeat_days_per_week, selected_days, repeat_until, price_1, price_2, price_5, price_12)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                    );
                    $stmt->bind_param(
                        'isssssisssddd',
                        $tutor_id, $subject_name, $topic_name, $class_date, $class_time,
                        $repeat_type, $repeat_days_per_week, $selected_days_str, $db_repeat_until,
                        $price_1, $price_2, $price_5, $price_12
                    );
                    if ($stmt->execute()) {
                        $message = 'Schedule saved successfully.';
                    } else {
                        $error = 'Could not save schedule. Check DB connection.';
                    }
                    $stmt->close();
                }
            }
        }
    }
}

$editing_id       = (int) ($_GET['edit'] ?? ($_POST['edit_schedule_id'] ?? 0));
$editing_schedule = null;

if ($editing_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM tutor_schedule WHERE id = ? AND tutor_id = ? LIMIT 1");
    $stmt->bind_param('ii', $editing_id, $tutor_id);
    $stmt->execute();
    $editing_schedule = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$form_values = [
    'subject_name' => $_POST['subject_name'] ?? ($editing_schedule['subject_name'] ?? ''),
    'topic_name'   => $_POST['topic_name']   ?? ($editing_schedule['topic_name']   ?? ''),
    'class_date'   => $_POST['class_date']   ?? ($editing_schedule['class_date']   ?? ''),
    'class_time'   => $_POST['class_time']   ?? ($editing_schedule['class_time']   ?? ''),
    'repeat_type'  => $_POST['repeat_type']  ?? ($editing_schedule['repeat_type']  ?? 'once'),
    'repeat_until' => $_POST['repeat_until'] ?? ($editing_schedule['repeat_until'] ?? ''),
    'weekdays'     => $_POST['weekdays']     ?? [],
    'price_1'      => $_POST['price_1']      ?? ($editing_schedule['price_1']      ?? 2.00),
    'price_2'      => $_POST['price_2']      ?? ($editing_schedule['price_2']      ?? 4.00),
    'price_5'      => $_POST['price_5']      ?? ($editing_schedule['price_5']      ?? 8.00),
    'price_12'     => $_POST['price_12']     ?? ($editing_schedule['price_12']     ?? 20.00),
];

if (!is_array($form_values['weekdays'])) {
    $form_values['weekdays'] = [];
}

$latest_schedules = [];
$stmt = $conn->prepare(
    "SELECT * FROM tutor_schedule WHERE tutor_id = ? ORDER BY created_at DESC, id DESC LIMIT 6"
);
$stmt->bind_param('i', $tutor_id);
$stmt->execute();
$latest_result = $stmt->get_result();
while ($row = $latest_result->fetch_assoc()) {
    $latest_schedules[] = $row;
}
$stmt->close();

include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/navbar.php';
?>

    <style>
        .schedule-layout {
            display: grid;
            gap: 18px;
        }

        .schedule-form {
            display: grid;
            gap: 12px;
        }

        .schedule-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .schedule-form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .schedule-form-group label {
            font-size: 0.84rem;
            font-weight: 600;
            color: #374151;
        }

        .schedule-form-group input,
        .schedule-form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-size: 0.9rem;
            color: #111827;
            background: #f9fafb;
        }

        .schedule-form-group input:focus,
        .schedule-form-group select:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
            background: #ffffff;
        }

        .schedule-form-help {
            font-size: 0.76rem;
            color: #6b7280;
            margin-top: -2px;
        }

        .schedule-weekday-group {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 6px;
        }

        .schedule-weekday-option {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border: 1px solid #d1d5db;
            border-radius: 999px;
            background: #ffffff;
            font-size: 0.8rem;
            color: #374151;
        }

        .schedule-weekday-option input {
            width: 14px;
            height: 14px;
            margin: 0;
        }

        .schedule-actions {
            display: flex;
            justify-content: flex-start;
        }

        .schedule-save-btn {
            border: none;
            border-radius: 10px;
            padding: 10px 16px;
            font-size: 0.9rem;
            font-weight: 700;
            color: #ffffff;
            background: linear-gradient(135deg, #4f46e5, #2563eb);
            cursor: pointer;
        }

        .schedule-save-btn:hover {
            filter: brightness(1.05);
        }

        .schedule-cancel-link {
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 0.86rem;
            font-weight: 700;
            color: #374151;
            background: #e5e7eb;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            margin-left: 8px;
        }

        .schedule-message,
        .schedule-error {
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 0.85rem;
            margin-bottom: 6px;
        }

        .schedule-message {
            background: #dcfce7;
            border: 1px solid #86efac;
            color: #166534;
        }

        .schedule-error {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        .schedule-list-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-top: 12px;
        }

        .schedule-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 12px;
            box-shadow: 0 6px 18px rgba(2, 6, 23, 0.06);
        }

        .schedule-card h3 {
            font-size: 0.96rem;
            margin: 0 0 6px;
            color: #111827;
            letter-spacing: -0.02em;
        }

        .schedule-card p {
            margin: 0 0 6px;
            font-size: 0.84rem;
            color: #4b5563;
            line-height: 1.45;
        }

        .schedule-meta {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 8px;
        }

        .schedule-chip {
            font-size: 0.75rem;
            font-weight: 700;
            color: #312e81;
            background: #e0e7ff;
            padding: 4px 8px;
            border-radius: 999px;
        }

        .schedule-empty {
            color: #6b7280;
            font-size: 0.9rem;
            margin-top: 8px;
        }

        .schedule-card-actions {
            display: flex;
            gap: 8px;
            margin-top: 10px;
        }

        .schedule-action-link,
        .schedule-action-btn {
            border: none;
            border-radius: 8px;
            padding: 6px 10px;
            font-size: 0.76rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }

        .schedule-action-link {
            background: #e0e7ff;
            color: #1e3a8a;
        }

        .schedule-action-btn {
            background: #fee2e2;
            color: #991b1b;
        }

        @media (max-width: 860px) {
            .schedule-list-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .schedule-form-grid,
            .schedule-list-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="schedule-layout">
        <div class="card">
            <h1>Create Schedule</h1>
            <p>Add subject, optional topic, class date/time, and choose one-time or recurring class. Latest 6 entries are shown below.</p>

            <?php if ($message !== ''): ?>
                <div class="schedule-message"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="schedule-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" class="schedule-form">
                <input type="hidden" name="edit_schedule_id" value="<?php echo $editing_schedule ? (int)$editing_schedule['id'] : 0; ?>">
                <div class="schedule-form-grid">
                    <div class="schedule-form-group" style="position: relative;">
                        <label for="subject_name">Subject Name</label>
                        <input
                            id="subject_name"
                            type="text"
                            name="subject_name"
                            placeholder="e.g. Mathematics"
                            value="<?php echo htmlspecialchars($form_values['subject_name']); ?>"
                            autocomplete="off"
                            required>
                        <div id="subject_autocomplete_list" style="position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #e5e7eb; border-radius: 6px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); z-index: 1000; max-height: 200px; overflow-y: auto; display: none;"></div>
                    </div>

                    <div class="schedule-form-group">
                        <label for="topic_name">Topic Name (Optional)</label>
                        <input
                            id="topic_name"
                            type="text"
                            name="topic_name"
                            placeholder="e.g. Algebra Basics"
                                value="<?php echo htmlspecialchars($form_values['topic_name']); ?>">
                    </div>

                    <div class="schedule-form-group">
                        <label for="class_date">Class Date</label>
                        <input
                            id="class_date"
                            type="date"
                            name="class_date"
                                value="<?php echo htmlspecialchars($form_values['class_date']); ?>"
                            min="<?php echo date('Y-m-d'); ?>"
                            required>
                    </div>

                    <div class="schedule-form-group">
                        <label for="class_time">Class Time</label>
                        <input
                            id="class_time"
                            type="time"
                            name="class_time"
                                value="<?php echo htmlspecialchars($form_values['class_time']); ?>"
                            required>
                    </div>

                    <div class="schedule-form-group">
                        <label for="repeat_type">Repeat</label>
                        <select id="repeat_type" name="repeat_type">
                            <option value="once" <?php echo ($form_values['repeat_type'] === 'once') ? 'selected' : ''; ?>>One-time class</option>
                            <option value="daily" <?php echo ($form_values['repeat_type'] === 'daily') ? 'selected' : ''; ?>>Every day</option>
                            <option value="weekly_1" <?php echo ($form_values['repeat_type'] === 'weekly_1') ? 'selected' : ''; ?>>1 day in week</option>
                            <option value="weekly_2" <?php echo ($form_values['repeat_type'] === 'weekly_2') ? 'selected' : ''; ?>>2 days in week</option>
                            <option value="weekly_3" <?php echo ($form_values['repeat_type'] === 'weekly_3') ? 'selected' : ''; ?>>3 days in week</option>
                            <option value="weekly_4" <?php echo ($form_values['repeat_type'] === 'weekly_4') ? 'selected' : ''; ?>>4 days in week</option>
                            <option value="weekly_5" <?php echo ($form_values['repeat_type'] === 'weekly_5') ? 'selected' : ''; ?>>5 days in week</option>
                            <option value="weekly_6" <?php echo ($form_values['repeat_type'] === 'weekly_6') ? 'selected' : ''; ?>>6 days in week</option>
                            <option value="weekly_7" <?php echo ($form_values['repeat_type'] === 'weekly_7') ? 'selected' : ''; ?>>7 days in week</option>
                        </select>
                    </div>

                    <div class="schedule-form-group">
                        <label for="repeat_until">Repeat Until</label>
                        <input
                            id="repeat_until"
                            type="date"
                            name="repeat_until"
                            min="<?php echo date('Y-m-d'); ?>"
                            value="<?php echo htmlspecialchars($form_values['repeat_until']); ?>">
                        <div class="schedule-form-help">Required for daily and weekly repeats. Keep empty for one-time.</div>
                    </div>

                    <div class="schedule-form-group" id="weekdays_wrap" style="grid-column: 1 / -1; display: none;">
                        <label>Select Week Day(s)</label>
                        <div class="schedule-weekday-group">
                            <label class="schedule-weekday-option"><input type="checkbox" name="weekdays[]" value="1" <?php echo in_array('1', $form_values['weekdays'], true) ? 'checked' : ''; ?>>Mon</label>
                            <label class="schedule-weekday-option"><input type="checkbox" name="weekdays[]" value="2" <?php echo in_array('2', $form_values['weekdays'], true) ? 'checked' : ''; ?>>Tue</label>
                            <label class="schedule-weekday-option"><input type="checkbox" name="weekdays[]" value="3" <?php echo in_array('3', $form_values['weekdays'], true) ? 'checked' : ''; ?>>Wed</label>
                            <label class="schedule-weekday-option"><input type="checkbox" name="weekdays[]" value="4" <?php echo in_array('4', $form_values['weekdays'], true) ? 'checked' : ''; ?>>Thu</label>
                            <label class="schedule-weekday-option"><input type="checkbox" name="weekdays[]" value="5" <?php echo in_array('5', $form_values['weekdays'], true) ? 'checked' : ''; ?>>Fri</label>
                            <label class="schedule-weekday-option"><input type="checkbox" name="weekdays[]" value="6" <?php echo in_array('6', $form_values['weekdays'], true) ? 'checked' : ''; ?>>Sat</label>
                            <label class="schedule-weekday-option"><input type="checkbox" name="weekdays[]" value="7" <?php echo in_array('7', $form_values['weekdays'], true) ? 'checked' : ''; ?>>Sun</label>
                        </div>
                        <div class="schedule-form-help">For example, if you choose "3 days in week", select exactly 3 days.</div>
                    </div>

                    <div class="schedule-form-group" style="grid-column: 1 / -1;">
                        <label>Tiered Pricing Layers (PKR)</label>
                        <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:12px;">
                            <div class="schedule-form-group">
                                <label style="font-size:0.75rem;">1 Class Fee</label>
                                <input type="number" step="0.01" name="price_1" value="<?php echo htmlspecialchars($form_values['price_1']); ?>" required>
                            </div>
                            <div class="schedule-form-group">
                                <label style="font-size:0.75rem;">2 Classes Fee</label>
                                <input type="number" step="0.01" name="price_2" value="<?php echo htmlspecialchars($form_values['price_2']); ?>" required>
                            </div>
                            <div class="schedule-form-group">
                                <label style="font-size:0.75rem;">5 Classes Fee</label>
                                <input type="number" step="0.01" name="price_5" value="<?php echo htmlspecialchars($form_values['price_5']); ?>" required>
                            </div>
                            <div class="schedule-form-group">
                                <label style="font-size:0.75rem;">Full Course Fee</label>
                                <input type="number" step="0.01" name="price_12" value="<?php echo htmlspecialchars($form_values['price_12']); ?>" required>
                            </div>
                        </div>
                        <div class="schedule-form-help">Set custom prices for different enrollment depth (e.g. Rs. 200, Rs. 400, Rs. 800, Rs. 2000).</div>
                    </div>
                </div>

                <div class="schedule-actions">
                    <button type="submit" name="save_schedule" class="schedule-save-btn"><?php echo $editing_schedule ? 'Update Schedule' : 'Save Schedule'; ?></button>
                    <?php if ($editing_schedule): ?>
                        <a href="create_schedule.php" class="schedule-cancel-link">Cancel Edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="card">
            <h2 style="margin-bottom:8px;">Latest Schedule Cards</h2>

            <?php if (count($latest_schedules) === 0): ?>
                <p class="schedule-empty">No schedules added yet.</p>
            <?php else: ?>
                <div class="schedule-list-grid">
                    <?php foreach ($latest_schedules as $item): ?>
                        <div class="schedule-card">
                            <h3><?php echo htmlspecialchars($item['subject_name']); ?></h3>
                            <p>
                                <?php if (!empty($item['topic_name'])): ?>
                                    Topic: <?php echo htmlspecialchars($item['topic_name']); ?>
                                <?php else: ?>
                                    Topic: General
                                <?php endif; ?>
                            </p>
                            <div class="schedule-meta">
                                <span class="schedule-chip"><?php echo htmlspecialchars($item['class_date']); ?></span>
                                <span class="schedule-chip"><?php echo htmlspecialchars(date('h:i A', strtotime($item['class_time']))); ?></span>
                                <span class="schedule-chip">
                                    <?php
                                    $repeat_label = $item['repeat_type'] ?? 'once';
                                    if ($repeat_label === 'daily') {
                                        echo 'Every Day';
                                    } elseif (preg_match('/^weekly_([1-7])$/', $repeat_label, $repeat_match) === 1) {
                                        if (!empty($item['selected_days'])) {
                                            $days_map = ['1'=>'Mon','2'=>'Tue','3'=>'Wed','4'=>'Thu','5'=>'Fri','6'=>'Sat','7'=>'Sun'];
                                            $d_arr = explode(',', $item['selected_days']);
                                            $d_names = array_map(function($d) use ($days_map) { return $days_map[$d] ?? $d; }, $d_arr);
                                            echo htmlspecialchars(implode(', ', $d_names));
                                        } else {
                                            echo htmlspecialchars($repeat_match[1]) . ' Day(s)/Week';
                                        }
                                    } else {
                                        echo 'One-time';
                                    }
                                    ?>
                                </span>
                                <?php if (!empty($item['repeat_until'])): ?>
                                    <span class="schedule-chip">Until: <?php echo htmlspecialchars($item['repeat_until']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="schedule-meta" style="margin-top:4px;">
                                <span class="schedule-chip" style="background:#fef3c7; color:#92400e;">1 Class: Rs. <?php echo number_format($item['price_1'], 2); ?></span>
                                <span class="schedule-chip" style="background:#fef3c7; color:#92400e;">5 Class: Rs. <?php echo number_format($item['price_5'], 2); ?></span>
                                <span class="schedule-chip" style="background:#fef3c7; color:#92400e;">Full Course: Rs. <?php echo number_format($item['price_12'], 2); ?></span>
                            </div>
                            <div class="schedule-card-actions">
                                <a class="schedule-action-link" href="create_schedule.php?edit=<?php echo (int)($item['id'] ?? 0); ?>">Edit</a>
                                <form method="POST" style="display:inline; margin:0;">
                                    <input type="hidden" name="delete_schedule_id" value="<?php echo (int)($item['id'] ?? 0); ?>">
                                    <button type="submit" name="delete_schedule" class="schedule-action-btn" onclick="return confirm('Delete this schedule?');">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        (function () {
            const repeatType = document.getElementById('repeat_type');
            const repeatUntil = document.getElementById('repeat_until');
            const weekdaysWrap = document.getElementById('weekdays_wrap');

            function syncRepeatUntilRequirement() {
                const isRecurring = repeatType.value !== 'once';
                const isWeeklyCount = repeatType.value.indexOf('weekly_') === 0;
                repeatUntil.required = isRecurring;
                weekdaysWrap.style.display = isWeeklyCount ? 'flex' : 'none';
                if (!isRecurring) {
                    repeatUntil.value = '';
                }
            }

            repeatType.addEventListener('change', syncRepeatUntilRequirement);
            syncRepeatUntilRequirement();

            // Autocomplete for Subject Name
            const subjectInput = document.getElementById('subject_name');
            const autocompleteList = document.getElementById('subject_autocomplete_list');

            let debounceTimer;
            subjectInput.addEventListener('input', function() {
                clearTimeout(debounceTimer);
                const query = this.value.trim();
                
                if (query.length === 0) {
                    autocompleteList.style.display = 'none';
                    return;
                }

                debounceTimer = setTimeout(async () => {
                    try {
                        const res = await fetch(`../ajax/search_subjects.php?q=${encodeURIComponent(query)}`);
                        const subjects = await res.json();
                        
                        if (subjects.length > 0) {
                            autocompleteList.innerHTML = '';
                            subjects.forEach(subject => {
                                const item = document.createElement('div');
                                item.style.padding = '8px 12px';
                                item.style.cursor = 'pointer';
                                item.style.borderBottom = '1px solid #f1f5f9';
                                item.style.fontSize = '0.9rem';
                                item.style.color = '#334155';
                                item.textContent = subject;
                                
                                item.addEventListener('mouseenter', () => {
                                    item.style.backgroundColor = '#f8fafc';
                                    item.style.color = '#10b981';
                                });
                                item.addEventListener('mouseleave', () => {
                                    item.style.backgroundColor = 'transparent';
                                    item.style.color = '#334155';
                                });
                                
                                item.addEventListener('click', function() {
                                    subjectInput.value = subject;
                                    autocompleteList.style.display = 'none';
                                });
                                autocompleteList.appendChild(item);
                            });
                            autocompleteList.style.display = 'block';
                        } else {
                            autocompleteList.style.display = 'none';
                        }
                    } catch (error) {
                        console.error('Error fetching subjects:', error);
                    }
                }, 300);
            });

            // Close the autocomplete list if the user clicks outside of it
            document.addEventListener('click', function (e) {
                if (e.target !== subjectInput && !autocompleteList.contains(e.target)) {
                    autocompleteList.style.display = 'none';
                }
            });
        })();
    </script>

<?php
include_once __DIR__ . '/../includes/footer.php';
?>


