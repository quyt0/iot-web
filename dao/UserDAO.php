<?php
    class UserDAO
    {
        public function __construct(private PDO $pdo) {}

        public function findByUsername(string $username): ?array
        {
            $stmt = $this->pdo->prepare('SELECT id, username, password_hash, full_name, student_id FROM `user` WHERE username = ?');
            $stmt->execute([$username]);
            return $stmt->fetch() ?: null;
        }

        public function findProfile(int $id): ?array
        {
            $stmt = $this->pdo->prepare('
                SELECT id, username, full_name, student_id, class_name, group_name, avatar_url,
                       github_url, figma_url, api_docs_url, report_pdf_url
                FROM `user` WHERE id = ?
            ');
            $stmt->execute([$id]);
            return $stmt->fetch() ?: null;
        }
    }
?>