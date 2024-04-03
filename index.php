<?php
session_start();

if (isset($_SESSION['login'])) {
    $login = $_SESSION['login'];
} else {
    $login = null;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $login = $_POST['login'];
    $_SESSION['login'] = $login;
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

class Database {
    private $db;

    public function __construct() {
        $this->db = new PDO('mysql:host=localhost;dbname=test_db;charset=utf8', 'admin', '111111');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function getUserQuestionCount($login, $question) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM questions WHERE login = :login AND question = :question");
        $stmt->execute(array(':login' => $login, ':question' => $question));
        return $stmt->fetchColumn();
    }

    public function saveQuestion($login, $question) {
        $stmt = $this->db->prepare("INSERT INTO questions (login, question) VALUES (:login, :question)");
        $stmt->execute(array(':login' => $login, ':question' => $question));
    }
}


class Magic8Ball {
    private $answers = [
        "Да.",
        "Нет.",
        "Возможно.",
        "Вопрос не ясен.",
        "Абсолютно точно.",
        "Никогда.",
        "Даже не думай.",
        "Сконцентрируйся и спроси опять."
    ];

    public function getAnswer() {
        return $this->answers[array_rand($this->answers)];
    }
}

$database = new Database();
$magic8Ball = new Magic8Ball();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['question'])) {
    if (isset($_SESSION['login'])) {
        $login = $_SESSION['login'];
    } else {
        header('Location: login.php');
        exit();
    }

    $login = $_POST['login']; 
    $question = $_POST['question']; 


    $database->saveQuestion($login, $question);

    $questionCount = $database->getUserQuestionCount($login, $question);

    $answer = $magic8Ball->getAnswer();

    echo json_encode(array('answer' => $answer, 'question_count' => $questionCount));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=MedievalSharp&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Marck+Script&display=swap" rel="stylesheet">
    <title>Спроси магический шар!</title>
</head>
<body>
    <h1 class="title">Magic ball is waiting for your question...</h1>
    <?php if ($login): ?>
    <form action="index.php" method="post" id="login-form">
        <label for="login">Введите ваш логин:</label><br>
        <input type="text" id="login" name="login" required><br>
        <button type="submit">Войти</button>
    </form>
    <?php else: ?>
    <form id="question-form">
        <label class="label-text" for="question">Введите ваш вопрос:</label><br>
        <input class="input-text" type="text" id="question" name="question" required><br>
        <button class="button-color" type="submit" id="main-button">Get answer</button>
    </form>
    <div class="answer-div" id="answer"></div>
    <?php endif; ?>
    <script>

        var questionInput = document.getElementById('question');
        var submitButton = document.querySelector('#question-form button');

        questionInput.addEventListener('input', function() {
            if (questionInput.value.trim() !== '') {
                submitButton.style.display = 'inline-block';
            } else {
                submitButton.style.display = 'none';
            }
        });

        document.getElementById('question-form').addEventListener('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            fetch('index.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('answer').innerHTML = data.answer;
                alert('Вопрос был задан ' + data.question_count + ' раз(а).');
            })
            .catch(error => console.error('Ошибка:', error));
        });

    </script>
</body>
</html>