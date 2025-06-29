<?php
// send_reminders.php
require __DIR__ . '/vendor/autoload.php'; // PHPMailer
require __DIR__ . '../config/db.php';

date_default_timezone_set('Asia/Manila');
$now = time();

// thresholds: [offset seconds, flag column, email subject]
$thresholds = [
  '3d'    => [3*24*3600, 'notified_3d',    'Reminder: Event in 3 Days'],
  '1d'    => [1*24*3600, 'notified_1d',    'Reminder: Event Tomorrow'],
  '1h'    => [1*3600,    'notified_1h',    'Reminder: Event in 1 Hour'],
  'start' => [0,         'notified_start', 'Event Is Starting Now!'],
];

// Common mailer setup
function sendMail($to, $name, $subject, $body) {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        // SMTP settings (use your own)
        $mail->isSMTP();
        $mail->Host       = 'smtp.yourhost.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USER'];
        $mail->Password   = $_ENV['SMTP_PASS'];
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('no-reply@yourdomain.com', 'Event Notifications');
        $mail->addAddress($to, $name);

        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->isHTML(true);
        $mail->send();
    } catch (Exception $e) {
        error_log("Mail error: {$mail->ErrorInfo}");
    }
}

foreach ($thresholds as $col => [$offset, $flag, $subject]) {
    // find events in window ±5 min of now + offset
    $startWindow = date('Y-m-d H:i:s', $now + $offset - 300);
    $endWindow   = date('Y-m-d H:i:s', $now + $offset + 300);

    $sql = "
      SELECT ep.participant_id, ep.name, ep.email,
             e.event_title, e.event_date, e.event_start_time
      FROM event_participants ep
      JOIN events e ON ep.event_id = e.event_id
      WHERE e.event_status = 'upcoming'
        AND e.event_date = ?
        AND ep.{$flag} = 0
        AND CONCAT(e.event_date, ' ', e.event_start_time) BETWEEN ? AND ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      date('Y-m-d', $now + $offset), 
      $startWindow, 
      $endWindow
    ]);

    while ($row = $stmt->fetch()) {
        // build email body
        $body = "
          Hi {$row['name']},<br><br>
          This is a reminder that <strong>{$row['event_title']}</strong><br>
          is scheduled for {$row['event_date']} at {$row['event_start_time']}.<br><br>
          See you there!
        ";
        sendMail($row['email'], $row['name'], $subject, $body);

        // mark as sent
        $update = $pdo->prepare(
          "UPDATE event_participants SET {$flag} = 1 WHERE participant_id = ?"
        );
        $update->execute([$row['participant_id']]);
    }
}

// cancelled notifications
$stmt = $pdo->prepare("
  SELECT ep.participant_id, ep.name, ep.email, e.event_title
  FROM event_participants ep
  JOIN events e ON ep.event_id = e.event_id
  WHERE e.event_status = 'cancelled'
    AND ep.notified_cancelled = 0
");
$stmt->execute();
while ($row = $stmt->fetch()) {
    $body = "
      Hi {$row['name']},<br><br>
      We’re sorry to inform you that <strong>{$row['event_title']}</strong><br>
      has been <span style='color:red;'>CANCELLED</span>.<br><br>
      We apologize for any inconvenience.
    ";
    sendMail($row['email'], $row['name'], 'Event Cancelled', $body);

    $update = $pdo->prepare(
      "UPDATE event_participants SET notified_cancelled = 1 WHERE participant_id = ?"
    );
    $update->execute([$row['participant_id']]);
}
