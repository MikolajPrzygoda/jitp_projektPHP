<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Nowy wpis do twojego bloga</title>
    <link rel="stylesheet" type="text/css" href="base.css" />
</head>

<body>
    <div id="content">
        <?PHP require 'menu.html' ?>

        <?php
        if($_POST){
            $sem = sem_get(1);
            sem_acquire($sem);
            //1. Find blog with given credentials
            $blogs = opendir('blogs');
            $found = FALSE;
            while(($blog = readdir($blogs)) !== FALSE && !$found){
                if($blog === '.' || $blog === '..'){
                    continue;
                }
                if(is_dir("blogs/$blog")){
                    $info = file_get_contents("blogs/$blog/info");
                    list($login, $hash, $desc) = explode("\n", $info);
                    if($_POST['userLogin'] === $login && md5($_POST['userPassword']) === $hash){
                        $found = TRUE;
                    }
                }
            }
            closedir($blogs);
            sem_release($sem);

            if(!$found){
                echo "<div style=\"text-align:center;\">";
                echo "<p style=\"font-size:2em; color:red;\">Błąd</p>";
                echo "<p>Nie znaleziono bloga z podanymi danymi uwierzytelniającymi: login:'{$_POST['userLogin']}', hasło:'{$_POST['userPassword']}'.</p>";
                echo "<a href=\"wpis.php\">Powrót</a>";
                echo "</div>";
            }
            else {
                sem_acquire($sem);
                //2.Create blog entry
                //2.1 Build entry name
                list($R, $M, $D) = explode('-', $_POST['entryDate']);
                list($G, $m) = explode(':', $_POST['entryTime']);
                $S = date('s');
                /* Generate random U number and check if file with such name already exists,
                we're assuming no user will make 100 entries a second.*/
                while($U = rand(0, 99)){
                    if($U < 10){
                        $U = '0'+$U;
                    }
                    $entryName = "{$R}{$M}{$D}{$G}{$m}{$S}{$U}";
                    if(!file_exists("blogs/$blog/$entryName")){
                        break;
                    }
                }

                //2.2 Create and write to the entry file
                $entryFile = fopen("blogs/$blog/$entryName", 'w');
                fwrite($entryFile, filter_var($_POST['entryContent'], FILTER_SANITIZE_STRING));
                fclose($entryFile);
                sem_release($sem);
                sem_remove($sem);
                echo "<div style=\"text-align:center;\">";
                echo "<p>Witaj $login!</p><p>Dodano wpis do twojego blogu: $blog.</p>";

                //2.3 Create atachments files
                if($_FILES['atachments']){
                    for($i = 0; $i < 3; $i++){
                        $attachmentName = $_FILES['atachments']['name'][$i];
                        if($attachmentName !== ""){
                            $lastDotIndex = strrpos($attachmentName, '.');
                            $OOO = substr($attachmentName, $lastDotIndex);
                            if(move_uploaded_file($_FILES['atachments']['tmp_name'][$i], "blogs/$blog/$entryName$i$OOO")){
                                echo "<p>Załącznik nr $i został pomyślnie wysłany.</p>";
                            }
                            else{
                                echo "<p>Coś poszło nie tak z załącznikiem nr $i </p>";
                            }
                        }
                    }
                }
                echo "</div>";
                sem_release($sem);
                sem_remove($sem);
            }
        } else { ?>
    
        <form method="post" id="entryForm" enctype="multipart/form-data">
            <label>Login: <input name="userLogin" type="text" required/></label>
            <label>Hasło: <input name="userPassword" type="password" required/></label>
            <label class="hasTextArea">Treść wpisu:
                <textarea for="entryForm" cols=35 rows="5" name="entryContent" required></textarea>
            </label>
            <label>Data wpisu: <input name="entryDate" type="text" value="<?php echo date("Y-m-d") ?>" readonly required/></label>
            <label>Godzina (gg:mm): <input name="entryTime" type="text" value="<?php echo date("H:i") ?>" readonly required/></label>
            <p>Załączniki:</p>
            <input name="MAX_FILE_SIZE" type="hidden" value="2000000" />
            <input name="atachments[]" type="file" />
            <input name="atachments[]" type="file" />
            <input name="atachments[]" type="file" />
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
