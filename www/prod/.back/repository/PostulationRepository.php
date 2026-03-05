<?php
// prod/.back/repository/PostulationRepository.php

class PostulationRepository {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Enregistre une nouvelle candidature
     */
    public function apply(int $userId, int $offerId, array $data): bool {
        $sql = "INSERT INTO postulations (user_id, offer_id, message, cv_path) 
                VALUES (:uid, :oid, :msg, :cv)";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':uid' => $userId,
            ':oid' => $offerId,
            ':msg' => $data['message'] ?? null,
            ':cv'  => $data['cv_path'] ?? null
        ]);
    }

    /**
     * Vérifie si un étudiant a déjà postulé à une offre
     */
    public function hasAlreadyApplied(int $userId, int $offerId): bool {
        $stmt = $this->pdo->prepare("SELECT 1 FROM postulations WHERE user_id = ? AND offer_id = ?");
        $stmt->execute([$userId, $offerId]);
        return (bool)$stmt->fetch();
    }

    /**
     * Récupère l'historique des postulations d'un étudiant
     */
    public function getStudentHistory(int $userId): array {
        $sql = "SELECT p.*, o.position, o.location, c.name as company_name 
                FROM postulations p
                JOIN offers o ON p.offer_id = o.id
                JOIN companies c ON o.company_id = c.id
                WHERE p.user_id = :uid
                ORDER BY p.created_at DESC";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}