<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Stwórz swój blog</title>
    <link rel="stylesheet" type="text/css" href="base.css" />
    <link rel="stylesheet" type="text/css" href="nowy.css" />
</head>

<body>
    <div id="content">
        <?PHP require 'menu.html' ?>
        <?php 
        if($_POST){
            //Acquire semaphor
            $sem = sem_get(1);
            sem_acquire($sem); //Lock other threads
            if(file_exists("blogs/{$_POST['blogName']}")){
                echo "<div style=\"text-align:center;\">";
                echo "<p>Blog z podaną nazwą już istnieje, porszę wybrać inną.</p>";
                echo "<a href=\"nowy.php\">Powrót</a>";
                echo "</div>";
            }
            else{
                $blogName = filter_var($_POST['blogName'], FILTER_SANITIZE_STRING);
                mkdir("blogs/$blogName");
                $file = fopen("blogs/$blogName/info", 'w');
                fwrite($file, filter_var($_POST['userLogin'], FILTER_SANITIZE_STRING).'\n');
                //no sanitizing, user input goes right into md5 so no harm can be done here
                //(users can use special characters in their passwords - feature)
                fwrite($file, md5($_POST['userPassword']) . "\n"); 
                fwrite($file, filter_var($_POST['blogDescription'], FILTER_SANITIZE_STRING));
                fclose($file);

                echo "<div style=\"text-align:center;\">";
                echo "<p>Blog \"$blogName\" został utworzony.</p>";
                echo "<a href=\"blog.php?nazwa=$blogName\">Przejdź do swojego nowego bloga</a>";
                echo "</div>";
            }
            sem_release($sem);
            sem_remove($sem);
        } else { ?>
        <form method="post" id="blogFrom">
            <label>Login: <input name="userLogin" type="text" required/></label>
            <label>Hasło: <input name="userPassword" type="password" required/></label>
            <label>Nazwa bloga: <input name="blogName" type="text" required/></label>
            <label class="hasTextArea">Opis bloga: 
                <textarea for="blogForm" cols=35 rows="5" name="blogDescription" required></textarea>
            </label>
            <div class="formButtonsDivCenter">
                <div class="formButtonsDivInline">
                    <input type="submit" value="Wyślij"/>
                    <input type="reset" value="Wyczyść"/>
                </div>
            </div>
        </form>
        <?php } ?>
    </div>
</body>

</html>