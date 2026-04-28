<?php

class SessionManager
{

    private int    $sessionTimeout;
    private string $storageFile;

    public function __construct(int $sessionTimeout = 7200)
    {
        $this->sessionTimeout = $sessionTimeout;
        $this->storageFile    = __DIR__ . '/../logs/sessions.json';
        $this->cleanupExpired();
    }

    // ── Public API ────────────────────────────────────────────────────────

    public function getSession(string $sessionId): array
    {
        $sessions = $this->load();

        if (!isset($sessions[$sessionId])) {
            $sessions[$sessionId] = $this->defaultSession();
            $this->save($sessions);
        } else {
            $sessions[$sessionId]['last_activity'] = time();
            $this->save($sessions);
        }

        return $sessions[$sessionId];
    }

    public function update(string $sessionId, array $data): void
    {
        $sessions = $this->load();

        if (!isset($sessions[$sessionId])) {
            $sessions[$sessionId] = $this->defaultSession();
        }

        foreach ($data as $key => $value) {
            $sessions[$sessionId][$key] = $value;
        }

        $sessions[$sessionId]['last_activity'] = time();
        $this->save($sessions);
    }

    public function delete(string $sessionId): bool
    {
        $sessions = $this->load();

        if (!isset($sessions[$sessionId])) {
            return false;
        }

        unset($sessions[$sessionId]);
        $this->save($sessions);
        return true;
    }

    public function count(): int
    {
        return count($this->load());
    }

    // ── Private Helpers ───────────────────────────────────────────────────

    private function defaultSession(): array
    {
        return [
            'title'         => 'Sir',
            'name'          => null,
            'last_activity' => time(),
            'created_at'    => time(),
            'awaiting_followup' => null,
        ];
    }

    private function load(): array
    {
        if (!file_exists($this->storageFile)) {
            return [];
        }

        $data = file_get_contents($this->storageFile);
        if (!$data) {
            return [];
        }

        $sessions = json_decode($data, true);
        return is_array($sessions) ? $sessions : [];
    }

    private function save(array $sessions): void
    {
        file_put_contents(
            $this->storageFile,
            json_encode($sessions, JSON_PRETTY_PRINT),
            LOCK_EX
        );
    }

    private function cleanupExpired(): void
    {
        $sessions    = $this->load();
        $currentTime = time();
        $cleaned     = 0;

        foreach ($sessions as $id => $data) {
            $lastActivity = $data['last_activity'] ?? 0;
            if (($currentTime - $lastActivity) > $this->sessionTimeout) {
                unset($sessions[$id]);
                $cleaned++;
            }
        }

        if ($cleaned > 0) {
            $this->save($sessions);
        }
    }
}
