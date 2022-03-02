<?php

require("Database.php");

header('Content-Type:application/json; charset=utf-8;');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');


class ToDo implements JsonSerializable
{
    private ?int $id;
    private string $name;
    private string $description;
    private string $responsible;
    private ?string $creationDate;
    private string $untilDate;

    private Category $category;

    private function __construct(stdClass $obj)
    {
        $this->id = $obj->todo_id;
        $this->name = $obj->name;
        $this->description = $obj->description;
        $this->responsible = $obj->responsible;
        $this->creationDate = $obj->date_created;
        $this->untilDate = $obj->date_until;

        $this->category = Category::loadById($obj->category_id);
    }

    public static function loadById(int $id): ?ToDo
    {
        $result = null;
        $db = Database::getInstance();

        $statement = $db->prepare("SELECT * FROM todos WHERE todo_id = :id");
        $statement->execute([":id" => $id]);

        $obj = $statement->fetch(PDO::FETCH_OBJ);

        if ($obj != null) {
            $result = new self($obj);
        }

        return $result;
    }

    public static function loadAll(): array|ToDo
    {
        $result = null;
        $db = Database::getInstance();

        $statement = $db->prepare("SELECT * FROM todos");
        $statement->execute();

        $results = $statement->fetchAll(PDO::FETCH_OBJ);
        if ($results != null) {
            $result = [];
            $curritem = 0;
            foreach ($results as $curr) {
                $result[$curritem] = new self($curr);
                $curritem++;
            }
        }
        return $result;
    }

    public static function updateTodo(stdClass $obj)
    {
        $db = Database::getInstance();
        $todo = new self($obj);
        $catId = $todo->category->getId();
        $sql = "UPDATE todos SET ";

        if (isset($todo->name)) $sql .= "name='$todo->name', ";
        if (isset($todo->description)) $sql .= "description='$todo->description', ";
        if (isset($todo->untilDate)) $sql .= "date_until='$todo->untilDate', ";
        if (isset($todo->responsible)) $sql .= "responsible='$todo->responsible', ";
        if ($todo->category->getId() !== null) $sql .= "category_id='$catId' ";

        $sql .= "WHERE todo_id='$todo->id';";
        var_dump($sql);
        $statement = $db->prepare($sql);
        $statement->execute();
    }

    public static function addToDatabase(stdClass $obj)
    {
        $todo = new self($obj);

        $db = Database::getInstance();
        $catId = $todo->category->getId();
        $dateCreated = date('Y-m-d');
        $statement = $db->prepare("INSERT INTO todos (`name`, `description`, `date_created`, `date_until`, `responsible`, `category_id`) VALUES (:tname, :description, :date_created, :date_until, :responsible, :cat);");
        $statement->bindParam(":tname", $todo->name);
        $statement->bindParam(":description", $todo->description);
        $statement->bindParam(":date_created", $dateCreated);
        $statement->bindParam(":date_until", $todo->untilDate);
        $statement->bindParam(":responsible", $todo->responsible);
        $statement->bindParam(":cat", $catId);
        $statement->execute();
    }

    public static function deleteTodo(stdClass $obj)
    {
        $todo = new self($obj);
        $db = Database::getInstance();

        $statement = $db->prepare("DELETE FROM todos WHERE todo_id=" . $todo->id);
        $statement->execute();
    }

    public function jsonSerialize(): array
    {
        $vars = get_object_vars($this);

        return $vars;
    }
}

class Category implements JsonSerializable
{
    private int $id;
    private string $name;

    public function getId(): int
    {
        return $this->id;
    }

    private function __construct(stdClass $obj)
    {
        $this->id = $obj->category_id;
        $this->name = $obj->name;
    }

    public static function loadById(int $id): ?Category
    {
        $result = null;
        $db = Database::getInstance();

        $statement = $db->prepare("SELECT * FROM  categories WHERE category_id = :id");
        $statement->execute([":id" => $id]);

        $obj = $statement->fetch(PDO::FETCH_OBJ);

        if ($obj != null) {
            $result = new self($obj);
        }

        return $result;
    }

    public static function loadAll(): array
    {
        $result = null;
        $db = Database::getInstance();

        $statement = $db->prepare("SELECT * FROM  categories");
        $statement->execute();

        $results = $statement->fetchAll(PDO::FETCH_OBJ);

        if ($results != null) {
            $result = [];
            $currItem = 0;
            foreach ($results as $curr) {
                $result[$currItem] = new self($curr);
                $currItem++;
            }
        }

        return $result;
    }


    public function jsonSerialize(): array
    {
        $vars = get_object_vars($this);

        return $vars;
    }
}

function dictToStdClass($dict)
{
    $obj = new stdClass();
    $obj->todo_id = $dict['id'];
    $obj->name = $dict["name"];
    $obj->description = $dict["description"];
    $obj->date_until = $dict["date_until"];
    $obj->date_created = $dict["date_created"];
    $obj->responsible = $dict["responsible"];
    $obj->category_id = $dict["category_id"];
    return $obj;
}

function getDataFromPost(): array
{
    $json = json_decode(file_get_contents('php://input'));
    var_dump($json);
    $data = [
        'id' => intval($json->id),
        'name' => $json->name,
        'description' => $json->description,
        'date_until' => $json->untilDate,
        'date_created' => $json->createdDated,
        'responsible' => $json->responsible,
        'category_id' => intval($json->category->id)
    ];
    foreach ($data as $key => $value) {
        if (empty($value)) {
            $data[$key] = null;
        }
    }

    return $data;
}

$db = Database::getInstance();
switch ($_GET['option']) {
    case "getTodos":

        $todos = ToDo::loadAll();

        echo json_encode($todos);
        break;


    case "getTodo":
        if (isset($_GET['id'])) {
            $todo = ToDo::loadById(intval($_GET["id"]));
            echo json_encode($todo);
        }
        break;
    case "addTodo":
        /*
         * BSP:
         * INSERT INTO `todos` (`todo_id`, `name`, `description`, `date_created`, `date_until`, `responsible`, `category_id`)
         *  VALUES (NULL, 'MEDT lernen', 'String', current_timestamp(), '2021-11-30 08:47:53', 'Stefan', '2');
         */

            $data = getDataFromPost();
        if (isset($data["id"])) {
            $obj = dictToStdClass($data);

            Todo::addToDatabase($obj);

            header("Location: index.php/?option=getTodo&id=" . $db->lastInsertId());
        }

        break;
    case "getCategoryById":
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            $category = Category::loadById($id);

            echo json_encode($category);
        }
        break;
    case "updateTodo":
            $data = getDataFromPost();
            if (isset($data["id"])) {
                $obj = dictToStdClass($data);
                ToDo::updateTodo($obj);
                echo json_encode(ToDo::loadById($data['id']));
            }
        break;
    case "getCategories":
        echo json_encode(Category::loadAll());
        break;
    case "deleteTodo":
        $data = getDataFromPost();
        if (isset($data["id"])) {
            $obj = dictToStdClass($data);
            ToDo::deleteTodo($obj);
            echo json_encode(ToDo::loadById($data['id']));
        }
        break;
}