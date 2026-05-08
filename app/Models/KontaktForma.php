<?php
namespace Models;

use Core\Database as DB;

class KontaktForma
{
    public static function count(array $where = [], array $params = []): int
    {
        $w = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $stmt = DB::prepare("SELECT COUNT(*) FROM kontakt_forme $w");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public static function getPage(int $page, int $perPage, array $where = [], array $params = []): array
    {
        $w      = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $offset = ($page - 1) * $perPage;
        $stmt   = DB::prepare("SELECT * FROM kontakt_forme $w ORDER BY datum DESC LIMIT $perPage OFFSET $offset");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function countUnread(): int
    {
        return (int)DB::prepare("SELECT COUNT(*) FROM kontakt_forme WHERE procitano=0")
                      ->execute([]) ? 0 : 0;
    }

    public static function getUnreadCount(): int
    {
        $stmt = DB::prepare("SELECT COUNT(*) FROM kontakt_forme WHERE procitano=0");
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public static function toggleRead(int $id): int
    {
        DB::prepare("UPDATE kontakt_forme SET procitano = 1 - procitano WHERE id=?")->execute([$id]);
        $stmt = DB::prepare("SELECT procitano FROM kontakt_forme WHERE id=?");
        $stmt->execute([$id]);
        return (int)$stmt->fetch()['procitano'];
    }

    public static function updateField(int $id, string $field, string $value): void
    {
        $allowed = ['firma', 'grad', 'komentar'];
        if (!in_array($field, $allowed)) return;
        DB::prepare("UPDATE kontakt_forme SET $field=? WHERE id=?")->execute([$value, $id]);
    }

    public static function delete(int $id): void
    {
        DB::prepare("DELETE FROM kontakt_forme WHERE id=?")->execute([$id]);
    }
}
