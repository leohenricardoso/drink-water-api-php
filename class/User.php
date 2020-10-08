<?php
require_once('../config/database.php');

class User
{
    private $conn;
    private $tableName = "users";

    private $id;
    private $name;
    private $email;
    private $password;
    private $drink_counter = 0;
    private $drink_ml = 0;
    private $token;

    public function __construct($db, $name = '', $email = '', $password = '')
    {
        $this->conn = $db;

        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
    }

    public function create()
    {
        if ($this->emailExists($this->email)) {
            throw new \Exception('Email already taken.', 500);
        }

        $this->generateToken();

        $query = "INSERT INTO `users` (
            `email`,
            `name`,
            `password`,
            `drink_counter`,
            `token`
        ) VALUES (
            '{$this->email}',
            '{$this->name}',
            '{$this->password}',
            '{$this->drink_counter}',
            '{$this->token}'
        );";

        $data = $this->conn->prepare($query);
        $data->execute();
        return $data;
    }

    private function generateToken()
    {
        $this->token = sha1("{$this->email}{$this->password}");
    }

    public function emailExists(string $email)
    {
        $data = $this->getUserByEmail($email);
        $num = $data->rowCount();

        return $num;
    }

    public function getUserByEmail(string $email)
    {
        $this->emailFormat($email);

        $query = "SELECT
            `id`,
            `email`,
            `name`,
            `password`,
            `drink_counter`
        FROM
            `users`
        WHERE
            `email` = '{$email}';";

        $data = $this->conn->prepare($query);
        $data->execute();

        return $data;
    }

    public function emailFormat($email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format', 500);
        }
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public function getUserByToken(string $token)
    {

        $token = explode(' ', $token);
        $token = $token[1];

        $query = "SELECT
            `id`,
            `name`,
            `email`,
            `drink_counter`,
            `token`
        FROM
            `users`
        WHERE
            `token` = '{$token}';";

        $data = $this->conn->prepare($query);
        $data->execute();

        $num = $data->rowCount();
        if ($num > 0) {
            return true;
        }
        return false;
    }

    public function getAll()
    {
        $query = "SELECT * FROM " . $this->tableName . "";
        $data = $this->conn->prepare($query);
        $data->execute();

        $num = $data->rowCount();

        if ($num > 0) {

            $users = array();
            $users['itemCount'] = $num;
            $users['data'] = array();

            while ($row = $data->fetch(PDO::FETCH_ASSOC)) {
                extract($row);
                $items = array(
                    'id' => $id,
                    'name' => $name,
                    'email' => $email,
                    'password' => $password,
                    'drink_counter' => $drink_counter
                );
                $users['data'][] = $items;
            }

            http_response_code(200);
            echo json_encode($users);
        } else {
            http_response_code(404);

            echo json_encode(
                array("message" => "No users found.")
            );
        }
    }

    public function getById($id)
    {
        $query = "SELECT * FROM " . $this->tableName . " where id = " . $id;
        $data = $this->conn->prepare($query);
        $data->execute();

        $num = $data->rowCount();

        if ($num > 0) {

            while ($row = $data->fetch(PDO::FETCH_ASSOC)) {
                extract($row);
                $user = array(
                    'id' => $id,
                    'name' => $name,
                    'email' => $email,
                    'password' => $password,
                    'drink_counter' => $drink_counter,
                    'token' => $token
                );
            }

            return $user;
        } else {
            http_response_code(404);

            echo json_encode(
                array("message" => "No users found.")
            );
        }
    }

    public function setParameters(array $body)
    {
        $parameters = [];
        if (isset($body['name'])) {
            $parameters['name'] = $body['name'];
        }

        if (isset($body['email'])) {
            if ($this->emailExists($body['email'])) {
                throw new Exception('Email already taken.', 500);
            }

            $parameters['email'] = $body['email'];
        }

        if (isset($body['password'])) {
            $parameters['password'] = $body['password'];
        }

        return $parameters;
    }

    public function verifyUsersToken(string $token, int $iduser)
    {
        $user = $this->getById($iduser);

        if (!$this->getUserByToken($token)) {
            throw new Exception('Invalid token aaaa', 401);
        }

        $user['token'] = 'Bearer ' . $user['token'];

        if ($token != $user['token']) {
            throw new Exception('Token does not belong to user', 403);
        }


        return true;
    }

    public function update(int $iduser, array $parameters)
    {
        $needNewToken = false;
        $update = '';
        foreach ($parameters as $key => $value) {
            if ($key == 'password') {
                $this->password = $this->passwordToHash($value);
                $value = $this->password;
                $needNewToken = true;
            } else if ($key == 'email') {
                $needNewToken = true;
            }

            $update .= "`{$key}` = '{$value}', ";
        }

        if ($needNewToken) {
            $this->generateToken();
            $update .= "`token` = '{$this->token}', ";
        }

        $update = substr_replace($update, ' ', -2);

        $query = "UPDATE
            `users`
        SET
            {$update}
        WHERE
            `id` = {$iduser};";

        $data = $this->conn->prepare($query);
        $data->execute();

        http_response_code(200);
        echo json_encode(
            array("message" => "Success")
        );
    }

    private function passwordToHash(string $password): string
    {
        return sha1($password);
    }

    public function delete(int $id)
    {
        $query = "DELETE
        FROM
            `users`
        WHERE
            `id` = {$id};";

        $data = $this->conn->prepare($query);
        return $data->execute();
    }

    public function login(string $email, string $password)
    {
        $this->emailFormat($email);

        if (!$this->emailExists($email)) {
            throw new Exception('User not found', 404);
        }

        $password = $this->passwordToHash($password);

        $query = "SELECT
            `id`,
            `name`,
            `email`,
            `drink_counter`,
            `token`
        FROM
            `users`
        WHERE
            `email` = '{$email}'
            AND `password` = '{$password}';";

        $data = $this->conn->prepare($query);
        $data->execute();

        $num = $data->rowCount();

        if ($num > 0) {

            while ($row = $data->fetch(PDO::FETCH_ASSOC)) {
                extract($row);
                $user = array(
                    'id' => $id,
                    'name' => $name,
                    'email' => $email,
                    'password' => $password,
                    'drink_counter' => $drink_counter,
                    'token' => $token
                );
            }

            if (isset($user['token'])) {
                $this->generateToken();

                $query2 = "UPDATE
                    `users`
                SET
                    `token` = '{$this->token}'
                WHERE
                    `id` = '{$user['id']}';";

                $dataWithToken = $this->conn->prepare($query2);
                $dataWithToken->execute();

                $user['token'] = $this->token;
            }

            return $user;
        } else {
            http_response_code(404);
            echo json_encode(
                array("message" => "No user found, please, verify entered data.")
            );
        }

        return $result;
    }

    public function drink(int $id, int $drink_ml)
    {
        $query = "UPDATE
            `users`
        SET
            `drink_counter` = `drink_counter` + 1,
            `drink_ml` = `drink_ml` + $drink_ml
        WHERE
            `id` = {$id};";

        $data = $this->conn->prepare($query);
        $data->execute();

        return true;
    }
}
