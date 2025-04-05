<?php
function getDBConnection()
{
    $dbPath = realpath('./submissions.db');

    if (!file_exists($dbPath)) {
        touch($dbPath);
        chmod($dbPath, 0664);
    }

    static $db = null;

    if ($db === null) {
        try {
            $db = new PDO("sqlite:$dbPath", null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_PERSISTENT => false
            ]);

            // Silent table creation
            $db->exec("PRAGMA journal_mode = MEMORY");
            $db->exec("CREATE TABLE IF NOT EXISTS submissions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL,
                phone TEXT NOT NULL,
                message TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            throw new Exception("Database operation failed");
        }
    }
    return $db;
}

function saveSubmission($data) {
    $db = getDBConnection();
    
    try {
        $stmt = $db->prepare("INSERT INTO submissions 
            (name, email, phone, message) 
            VALUES (:name, :email, :phone, :message)");
            
        $stmt->execute([
            ':name'    => $data['name'],
            ':email'   => $data['email'],
            ':phone'   => $data['phone'],
            ':message' => $data['message']
        ]);
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        throw new Exception("Error saving submission to database");
    }
}
