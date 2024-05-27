<?php

/**
 * TelegramPhp Database Backup Library
 *
 * This class facilitates database backup operations by exporting tables and data into a SQL file
 * and sending it to a Telegram chat. This library is useful for automating database backups
 * and sending them to specified Telegram chats for storage and monitoring.
 *
 * @package Expertskb\TelegramPhp
 * @author Shakib Ahmed
 * @version 1.2
 */

namespace Expertskb\TelegramPhp;

use DateTimeZone;
use DateTime;
use mysqli;
use CURLFile;

class Backup
{
    private $database;
    private $password;
    private $host;
    private $username;
    private $bot_token;
    private $chat_id;
    private $debug = false;

    public function __construct($host, $username, $database, $passowrd, $bot_token, $chat_id = [])
    {
        $this->host = $host;
        $this->username = $username;
        $this->database = $database;
        $this->password = $passowrd;
        $this->bot_token = $bot_token;
        $this->chat_id = $chat_id;
    }

    public function setDeBug($command)
    {
        $this->debug = $command;
    }

    public function run()
    {
        // Export the database and get the file path
        $__PATH = $this->exportDatabase();

        // Check if the path is a valid file
        if (is_file($__PATH)) {

            // Ensure $chat_id is an array
            $chat_ids = is_array($this->chat_id) ? $this->chat_id : (array) $this->chat_id;

            // Check if the path is not empty and $chat_ids is an array
            if (!empty($__PATH) && is_array($chat_ids)) {
                foreach ($chat_ids as $ch_id) {
                    // Send the file via Telegram
                    $data = json_decode($this->Telegram($__PATH, $ch_id), true);

                    // Check if the data is valid and the operation was successful
                    if ($data !== null && isset($data['ok']) && $data['ok']) {
                        // If debugging is enabled, output the data
                        if (is_bool($this->debug) && $this->debug) {
                            echo json_encode($data);
                        }
                    }
                }

                // Clean up the file after all iterations
                if (file_exists($__PATH)) {
                    if (chmod($__PATH, 0755)) {
                        if (unlink($__PATH)) {
                            // File successfully deleted
                        }
                    }
                }
            }
        }
    }


    protected function exportDatabase()
    {
        $tables = false;
        $backup_name = false;
        $dateTime = new DateTime('now', new DateTimeZone('Asia/Dhaka'));
        $date = $dateTime->format("M, d Y") . ' at ' . $dateTime->format(" H:i A");
        set_time_limit(3000);
        $mysqli = new mysqli($this->host, $this->username, $this->password, $this->database);
        $mysqli->select_db($this->database);
        $mysqli->query("SET NAMES 'utf8'");
        $queryTables = $mysqli->query('SHOW TABLES');
        while ($row = $queryTables->fetch_row()) {
            $target_tables[] = $row[0];
        }
        if ($tables !== false) {
            $target_tables = array_intersect($target_tables, $tables);
        }
        $content = "-- PhpMyAdmin\n";
        $content .= "-- https://shakib.eu.org\n";
        $content .= "-- \n";
        $content .= "-- Generation Time: " . $date . "\n";
        $content .= "-- MySQL Server Version: " . $mysqli->server_info . "\n";
        $content .= "-- PHP Version: " . phpversion() . "\n\n";
        $content .= "\r\n\r\nSET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\r\nSET time_zone = \"+00:00\";\r\n\r\n\r\n/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\r\n/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\r\n/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\r\n/*!40101 SET NAMES utf8mb4 */;\r\n--\r\n-- Database: `" . $this->database . "`\r\n--\r\n\r\n\r\n";
        foreach ($target_tables as $table) {
            if (empty($table)) {
                continue;
            }
            $result = $mysqli->query('SELECT * FROM `' . $table . '`');
            $fields_amount = $result->field_count;
            $rows_num = $mysqli->affected_rows;
            $res = $mysqli->query('SHOW CREATE TABLE ' . $table);
            $TableMLine = $res->fetch_row();
            $content .= "\n\n" . $TableMLine[1] . ";\n\n";
            $TableMLine[1] = str_ireplace('CREATE TABLE `', 'CREATE TABLE IF NOT EXISTS `', $TableMLine[1]);
            for ($i = 0, $st_counter = 0; $i < $fields_amount; $i++, $st_counter = 0) {
                while ($row = $result->fetch_row()) { //when started (and every after 100 command cycle):
                    if ($st_counter % 100 == 0 || $st_counter == 0) {
                        $content .= "\nINSERT INTO " . $table . " VALUES";
                    }
                    $content .= "\n(";
                    for ($j = 0; $j < $fields_amount; $j++) {
                        $row[$j] = str_replace("\n", "\\n", addslashes($row[$j]));
                        if (isset($row[$j])) {
                            $content .= '"' . $row[$j] . '"';
                        } else {
                            $content .= '""';
                        }
                        if ($j < ($fields_amount - 1)) {
                            $content .= ',';
                        }
                    }
                    $content .= ")";
                    //every after 100 command cycle [or at last line] ....p.s. but should be inserted 1 cycle eariler
                    if ((($st_counter + 1) % 100 == 0 && $st_counter != 0) || $st_counter + 1 == $rows_num) {
                        $content .= ";";
                    } else {
                        $content .= ",";
                    }
                    $st_counter = $st_counter + 1;
                }
            }
            $content .= "\n\n\n";
        }

        $content .= "\r\n\r\n/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\r\n/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\r\n/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;";
        $backup_name = $backup_name ? $backup_name : $this->database . ' __ (' . date('hsdmY') . ').sql';

        // Write content to file
        $file = fopen($backup_name, "w");
        fwrite($file, $content);
        fclose($file);

        return $backup_name;
    }

    protected function Telegram($backupPath, $chatID)
    {
        // URL to the Telegram API
        $telegramAPI = 'https://api.telegram.org/bot' . $this->bot_token . '/sendDocument';

        // Prepare the document to be sent
        $document = new CURLFile($backupPath);

        // Create POST request payload
        $postFields = array(
            'chat_id' => $chatID,
            'document' => $document,
        );

        // Initialize cURL session
        $ch = curl_init();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $telegramAPI);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:multipart/form-data'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

        // Execute cURL session
        $result = curl_exec($ch);

        // Close cURL session
        curl_close($ch);

        // Return the result
        return $result;
    }
}
