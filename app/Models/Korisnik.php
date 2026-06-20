<?php
namespace Models;

use Core\Database as DB;

class Korisnik
{
    public static function findByUsername(string $username): array|false
    {
        $stmt = DB::prepare("SELECT * FROM admin_korisnici WHERE username=? AND aktivan=1");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }

    public static function getAll(): array
    {
        // Dodaj kolone ako ne postoje
        try { DB::exec("ALTER TABLE admin_korisnici ADD COLUMN telegram_username VARCHAR(100) NOT NULL DEFAULT ''"); } catch (\PDOException $e) {}
        try { DB::exec("ALTER TABLE admin_korisnici ADD COLUMN platforma ENUM('android','ios','web') NOT NULL DEFAULT 'android'"); } catch (\PDOException $e) {}
        try { DB::exec("ALTER TABLE admin_korisnici ADD COLUMN platforma2 VARCHAR(20) DEFAULT NULL"); } catch (\PDOException $e) {}

        $stmt = DB::prepare(
            "SELECT id, ime, email, telefon, username, uloga, aktivan, vidi_imenik, vidi_magacin,
                    datum_kreiranja,
                    COALESCE(telegram_username,'') as telegram_username,
                    COALESCE(platforma,'android') as platforma,
                    platforma2
             FROM admin_korisnici ORDER BY id ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function create(array $data): void
    {
        DB::prepare(
            "INSERT INTO admin_korisnici (ime, email, username, password_hash, uloga, telefon, mail_pass, telegram_username, platforma, platforma2)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        )->execute([
            $data['ime'],
            $data['email'],
            $data['username'],
            password_hash($data['password'], PASSWORD_BCRYPT),
            $data['uloga'],
            $data['telefon']           ?? '',
            $data['mail_pass']         ?? '',
            $data['telegram_username'] ?? '',
            $data['platforma']         ?? 'android',
            $data['platforma2']        ?? null,
        ]);
    }

    public static function update(int $id, array $data): void
    {
        $tg        = $data['telegram_username'] ?? '';
        $platforma = in_array($data['platforma'] ?? '', ['android','ios','web'])
                        ? $data['platforma'] : 'android';
        $platforma2 = in_array($data['platforma2'] ?? '', ['android','ios','web'])
                        ? $data['platforma2'] : null;

        if (!empty($data['mail_pass'])) {
            DB::prepare(
                "UPDATE admin_korisnici
                 SET ime=?, email=?, telefon=?, mail_pass=?, telegram_username=?, platforma=?, platforma2=?
                 WHERE id=?"
            )->execute([$data['ime'], $data['email'], $data['telefon'], $data['mail_pass'], $tg, $platforma, $platforma2, $id]);
        } else {
            DB::prepare(
                "UPDATE admin_korisnici
                 SET ime=?, email=?, telefon=?, telegram_username=?, platforma=?, platforma2=?
                 WHERE id=?"
            )->execute([$data['ime'], $data['email'], $data['telefon'], $tg, $platforma, $platforma2, $id]);
        }
    }

    public static function resetPassword(int $id, string $password): void
    {
        DB::prepare("UPDATE admin_korisnici SET password_hash=? WHERE id=?")
          ->execute([password_hash($password, PASSWORD_BCRYPT), $id]);
    }

    public static function toggleAktivan(int $id, int $excludeId): array
    {
        DB::prepare("UPDATE admin_korisnici SET aktivan = 1 - aktivan WHERE id=? AND id!=?")
          ->execute([$id, $excludeId]);
        $stmt = DB::prepare("SELECT aktivan FROM admin_korisnici WHERE id=?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function toggleImenik(int $id): array
    {
        DB::prepare("UPDATE admin_korisnici SET vidi_imenik = 1 - vidi_imenik WHERE id=?")
          ->execute([$id]);
        $stmt = DB::prepare("SELECT vidi_imenik FROM admin_korisnici WHERE id=?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function toggleMagacin(int $id): array
    {
        DB::prepare("UPDATE admin_korisnici SET vidi_magacin = 1 - vidi_magacin WHERE id=?")
          ->execute([$id]);
        $stmt = DB::prepare("SELECT vidi_magacin FROM admin_korisnici WHERE id=?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function changeUloga(int $id, string $uloga): void
    {
        DB::prepare("UPDATE admin_korisnici SET uloga=? WHERE id=?")->execute([$uloga, $id]);
    }

    public static function delete(int $id): void
    {
        DB::prepare("DELETE FROM admin_korisnici WHERE id=?")->execute([$id]);
    }
}
