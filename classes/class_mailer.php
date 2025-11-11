<?php
class Mailer
{
    private string $from;
    private string $fromName;
    private array $cc = [];
    private array $bcc = [];
    private array $attachments = [];
    private bool $isProd = false;

    public function __construct() {
        global $env_server;
        $this->from = "noreply@ucdms.in";
        $this->fromName = "UCDMS IN";
        $this->isProd = ($env_server === "prod");
    }

    public function addCc(string|array $emails): void
    {
        $this->cc = array_merge($this->cc, (array)$emails);
    }

    public function addBcc(string|array $emails): void
    {
        $this->bcc = array_merge($this->bcc, (array)$emails);
    }

    public function addAttachment(string $filePath, string $filename = ''): void
    {
        if (file_exists($filePath)) {
            $this->attachments[] = [
                'path' => $filePath,
                'name' => $filename ?: basename($filePath)
            ];
        }
    }

    /**
     * Send email using PHP mail()
     */
    public function send(string $to, string $subject, string $body, bool $isHtml = true): bool
    {
        // Restrict email sending based on environment
        if (!$this->isProd) {
            $to = "bmwtech@cartrade.com";
            $this->cc = [];
            $this->bcc = [];
        }

        // Base headers
        $headers  = "From: {$this->fromName} <{$this->from}>\r\n";
        $headers .= "Reply-To: {$this->from}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";

        if (!empty($this->attachments)) {
            $boundary = md5(time());
            $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";

            $message  = "--{$boundary}\r\n";
            $message .= "Content-Type: text/" . ($isHtml ? "html" : "plain") . "; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $message .= chunk_split(base64_encode($body)) . "\r\n";

            foreach ($this->attachments as $file) {
                $fileContent = chunk_split(base64_encode(file_get_contents($file['path'])));
                $message .= "--{$boundary}\r\n";
                $message .= "Content-Type: application/octet-stream; name=\"{$file['name']}\"\r\n";
                $message .= "Content-Disposition: attachment; filename=\"{$file['name']}\"\r\n";
                $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $message .= $fileContent . "\r\n";
            }

            $message .= "--{$boundary}--";
        } else {
            $headers .= "Content-Type: text/" . ($isHtml ? "html" : "plain") . "; charset=UTF-8\r\n";
            $message = $body;
        }

        if (!empty($this->cc)) {
            $headers .= "Cc: " . implode(", ", $this->cc) . "\r\n";
        }
        if (!empty($this->bcc)) {
            $headers .= "Bcc: " . implode(", ", $this->bcc) . "\r\n";
        }
        $sent = mail($to, $subject, $message, $headers);
        $params = [
            'to' => $to,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
            'attachments' => array_map(function($a) { return $a['name'] ?? ''; }, $this->attachments)
        ];
        $response = $sent ? "Email sent to $to" : "Email failed to send to $to";
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        try {
            logMail($subject, $body, $params, $response, $ip);
        } catch (Exception $e) {
            error_log('logMail failed: ' . $e->getMessage());
        }
        return $sent;
    }
}
