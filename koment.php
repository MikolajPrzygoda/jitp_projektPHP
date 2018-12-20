<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Skomentuj wpis <?php echo $_GET['entryID']; ?></title>
    <link rel="stylesheet" type="text/css" href="base.css" />
</head>

<body>
    <div id="content">
    <?PHP require 'menu.html' ?>
    <?php if($_POST){
        $sem = sem_get($sem);
        list($blogName, $entry) = explode('-', $_POST['blogEntryID']);
        
        //Create entry's comment directory if it doesn't exist
        sem_acquire($sem);
        if(!file_exists("blogs/$blogName/$entry.k")){
            mkdir("blogs/$blogName/$entry.k");
        }
        sem_release($sem);

        //Find the latest comment
        sem_acquire($sem);
        $commentDir = opendir("blogs/$blogName/$entry.k");
        $maxCommentNumber = -1;
        while( ($commentFile = readdir($commentDir)) !== FALSE ){
            if($commentFile === '.' || $commentFile === '..'){
                continue;
            }
            if(intval(basename($commentFile)) > $maxCommentNumber){
                $maxCommentNumber = intval(basename($commentFile));
            }
        }
        closedir("blogs/$blogName/$entry.k");

        //Create the comment file
        $maxCommentNumber += 1;
        $commentFile = fopen("blogs/$blogName/$entry.k/$maxCommentNumber", 'w');
        fwrite($commentFile, filter_var($_POST['commentType'], FILTER_SANITIZE_STRING)."\n");
        fwrite($commentFile, date('Y-m-d, H:i:s')."\n");
        fwrite($commentFile, filter_var($_POST['commentAuthor'], FILTER_SANITIZE_STRING)."\n");
        fwrite($commentFile, filter_var($_POST['commentContent'], FILTER_SANITIZE_STRING));
        fclose($commentFile);
        sem_release($sem);

        //Print user feedback
        echo "<div style=\"text-align:center;\">";
        echo "<p>Komentarz został pomyślnie dodany.</p>";
        echo "<a href=\"blog.php?nazwa=$blogName#$entry\">Powrót do komentowanego wpisu</a>";
        echo "</div>";
    }
    else {?>
        <form method="post" id="commentFrom">
            <label>Komentowany wpis: 
                <input name="blogEntryID" type="text" value="<?php echo $_GET["entryID"] ?>" readonly required/>
            </label>
            <label>Rodzaj komentarza:
                <select name="commentType" required>
                    <option>Pozytywny</option>
                    <option>Neutralny</option>
                    <option>Negatywny</option>
                </select>
            </label>
            <label class="hasTextArea">Treść komentarza: 
                <textarea for="commentForm" cols=35 rows="5" name="commentContent" required></textarea>
            </label>
            <label>Imię/Nazw./Pseudonim: <input type="text" name="commentAuthor" required/></label>
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