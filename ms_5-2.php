<meta charset="UTF-8">
<?php
        # hostには「docker-compose.yml」で指定したコンテナ名を記載
        $dsn = 'mysql:dbname=tb230841db;host=localhost';
        $user = 'tb-230841';
        $password = 'Hu85ARnng7';
        $db = new PDO($dsn, $user, $password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));

        // 実行したいSQL文
        #事前にデータベースの作成
        // $sql1 = "CREATE DATABASE IF NOT EXISTS thread;";
        // $sql2 = "USE thread";
        // 事前にユーザーテーブルの作成
        $sql3 = "CREATE TABLE IF NOT EXISTS user ("
            ."id INT AUTO_INCREMENT PRIMARY KEY,"
            ."name VARCHAR(32) NOT NULL,"
            ."comment TEXT,"
            ."date TIMESTAMP"
        .");";
        // SQL文の実行
        // $stmt = $db->query($sql1);
        // $stmt = $db->query($sql2);
        $stmt = $db->query($sql3);

        // 事前にパスワードテーブルの作成
        $sql1 = "CREATE TABLE IF NOT EXISTS pass ("
            ."id INT AUTO_INCREMENT PRIMARY KEY,"
            ."name VARCHAR(32) NOT NULL,"
            ."pw VARCHAR(32),"
            ."date TIMESTAMP"
        .");";
        // SQL文の実行
        $stmt = $db->query($sql1);        
?>

<!-- 値が保持されないため読み込みなおしたときは新しいphpプログラムが始まる -->
<!-- そのためPHPの書く場所には気を付ける -->
<!-- コードを短くために自作関数とかないか調べてみる -->

<?php 
    // エラー回避用
    $edit_name = "";
    $edit_com = "";
    $edit_num = 0;
?>

<?php
    // ポスト受診した場合に動く
    if (!empty($_POST["name"]) && !empty($_POST["comment"]) && !empty($_POST["pass"])) {
        if (!empty($_POST["edit_form"])) {
            $name = $_POST["name"];
            $com = $_POST["comment"];
            $pass = $_POST["pass"];
            $id = $_POST["edit_form"];

            // idは変更しない、dateはタイムスタンプでおそらく自動更新⇒違うかった
            $sql = "UPDATE user SET name = :name, comment = :com WHERE id = :id";
            $stmt = $db -> prepare($sql);
            $stmt -> bindValue(':name', $name, PDO::PARAM_STR);
            $stmt -> bindValue(':com', $com, PDO::PARAM_STR);
            $stmt -> bindValue(':id', $id, PDO::PARAM_STR);
            $stmt -> execute();

            $sql = "UPDATE pass SET name = :name, pw = :pw WHERE id = :id";
            $stmt = $db -> prepare($sql);
            $stmt -> bindValue(':name', $name, PDO::PARAM_STR);
            $stmt -> bindValue(':pw', $pass, PDO::PARAM_STR);
            $stmt -> bindValue(':id', $id, PDO::PARAM_STR);
            $stmt -> execute();

        }else{
            #ファイルのデータ取得
            #ファイルデータの中身がある場合は更新
            $name = $_POST["name"];
            $com = $_POST["comment"];
            $pass = $_POST["pass"];

            #ユーザーテーブルへの保存
            $sql = "INSERT INTO user (name, comment, date) VALUES (:name, :comment, now())";
            $stmt = $db -> prepare($sql);
            $stmt -> bindValue(':name', $name, PDO::PARAM_STR);
            $stmt -> bindValue(':comment', $com, PDO::PARAM_STR);
            $stmt -> execute();
            #パスワードテーブルへの保存
            $sql = "INSERT INTO pass (name, pw, date) VALUES (:name, :pass, now())";
            $stmt = $db -> prepare($sql);
            $stmt -> bindValue(':name', $name, PDO::PARAM_STR);
            $stmt -> bindValue(':pass', $pass, PDO::PARAM_STR);
            $stmt -> execute();
            echo "登録しました";
        }

    }elseif(!empty($_POST["delete"]) && !empty($_POST["del_pass"])) {
        // パスワードも入力してくださいのバリデーションは
        // コメント履歴があって有効な数字のバリデーション
        // userテーブルにuser情報があるのかを確認⇒これが空なら検索失敗扱い
        $sql = "SELECT * FROM user WHERE id = :id";
        $stmt = $db -> prepare($sql);
        $del_id = (int)$_POST['delete'];
        $stmt -> bindParam(':id', $del_id, PDO::PARAM_INT);
        $stmt -> execute();
        $data = $stmt -> fetch();

        if ($data) {
            // パスワードのチェック
            $sql = "SELECT pw FROM pass WHERE id = :id";
            $stmt = $db -> prepare($sql);
            $stmt -> bindParam(':id', $del_id, PDO::PARAM_INT);
            $stmt -> execute();
            $result = $stmt -> fetch();
            $del_pw = $_POST['del_pass'];

            if ($result[0] == $del_pw) {
                // ここまであっていたら削除していい
                $sql = "DELETE FROM user WHERE id = :id";
                $stmt = $db -> prepare($sql);
                $stmt -> bindParam(':id', $del_id, PDO::PARAM_INT);
                $result = $stmt -> execute();

                $sql = "DELETE FROM pass WHERE id = :id";
                $stmt = $db -> prepare($sql);
                $stmt -> bindParam(':id', $del_id, PDO::PARAM_INT);
                $result = $stmt -> execute();
                echo "削除しました";
            }else{
                echo "パスワードが間違っています";
            }
        }else{
            echo "存在しないユーザです";
        }
    }elseif(!empty($_POST["edit"]) && !empty($_POST["ed_pass"])) {
        
        // userテーブルにuser情報があるのかを確認⇒これが空なら検索失敗扱い
        $sql = "SELECT * FROM user WHERE id = :id";
        $stmt = $db -> prepare($sql);
        $ed_id = (int)$_POST['edit'];
        $stmt -> bindParam(':id', $ed_id, PDO::PARAM_INT);
        $stmt -> execute();
        $data = $stmt -> fetch();

        if ($data) {
            // パスワードのチェック
            $sql = "SELECT pw FROM pass WHERE id = :id";
            $stmt = $db -> prepare($sql);
            $stmt -> bindParam(':id', $ed_id, PDO::PARAM_INT);
            $stmt -> execute();
            $result = $stmt -> fetch();
            $ed_pw = $_POST['ed_pass'];

            if ($result[0] == $ed_pw) {
                // フォームに代入
                $sql = "SELECT name, comment FROM user WHERE id = :id";
                $stmt = $db -> prepare($sql);
                $stmt -> bindParam(':id', $ed_id, PDO::PARAM_INT);
                $stmt -> execute();
                $result = $stmt -> fetch();

                $edit_name = $result[0];
                $edit_com = $result[1];
                $edit_num = $ed_id;
            }else{
                echo "パスワードが間違っています";
            }

        }else{
            echo "ユーザーが存在しません";
        }
        
    }
    
?>

<head>
    <style>
        form {
            width: 400px;
            padding: 20px;
            border-radius: 10px;
            background-color: #eeeeee;
        }
        p:first-child {
            margin-top: 0;
        }
        p:last-child {
            margin-bottom: 0;
        }
        label {
            display: inline-block;
            width: 120px;
        }
        input[type="text"], textarea {
            font-size: 1em;
            width: 280px;
            box-sizing: border-box;
        }
        textarea {
            vertical-align: top;
            height: 5em;
        }
        input[type="submit"] {
            margin-left: 120px;
        }
        .circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: skyblue;
        }
        tr {
            height: 50px;
        }
    </style>
</head>
<form method="POST">
    <p>
        <label>名前:</label><input type="text" name="name" value=<?= $edit_name ?>>
    </p>
    <p>
        <label>コメント:</label><textarea name="comment"><?= $edit_com ?></textarea>
    </p>
    <p>
        <label>パスワード:</label><input type="text" name="pass">
    </p>
    <p>
        <input type="submit"><input name="edit_form" value="<?= $edit_num ?>" style="height: 0;width: 0;" type="hidden">
    </p>
</form>
<form method="POST">
    <p>
        <label>削除番号:</label><input type="text" name="delete">
    </p>
    <p>
        <label>パスワード:</label><input type="text" name="del_pass">
    </p>
    <p>
        <input type="submit" value="削除">
    </p>
</form>
<form method="POST">
    <p>
        <label>編集対象番号:</label><input type="text" name="edit">
    </p>
    <p>
        <label>パスワード:</label><input type="text" name="ed_pass">
    </p>
    <p>
        <input type="submit" value="編集">
    </p>
</form>

<hr>
<table>
    <?php

        $sql = "SELECT * FROM user";
        $result = $db -> query($sql);
        $par1 = "<tr><td><p class='circle'></p></td><td><div style='width: 15px;'></div></td><td><p style='line-height: 10px; margin:0; font-size: 8px;'>";
        $par2 = "</p><p style='margin: 0;'>";
        $par3 = "</p></td></tr>";
        foreach ($result as $res) {

            $appear = $par1.$res[0].": ".$res[1]."   ".$res[3].$par2.$res[2].$par3;
            echo $appear;
        }
        $db = null;
    ?>
</table>
<hr>


