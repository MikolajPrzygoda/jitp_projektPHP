<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Projekt PHP - Blog: <?php echo $_GET['nazwa'] ?? "nie wybrany" ?></title>
    <link rel="stylesheet" type="text/css" href="base.css" />
    <link rel="stylesheet" type="text/css" href="blog.css" />
</head>

<body>
    <div id="content">
    <?php require 'menu.html' ?>
    
    <!-- No GET parameter -> Show list of all blogs on the site -->
    <?php if($_GET['nazwa'] == "") {
        $sem = sem_get(1);
        sem_acquire($sem);

        echo "<h1>Dostępne blogi:</h1>";
        echo "<ul id=\"blogList\">";
        $blogs = opendir('blogs');
        $blogNames = array();
        while(($blog = readdir($blogs)) !== FALSE){
            if($blog === '.' || $blog === '..'){
                continue;
            }
            if(is_dir("blogs/$blog")){
                array_push($blogNames, $blog);
            }
        }
        closedir($blogs);
        natsort($blogNames);
        foreach($blogNames as $blog){
            //Get blog's description
            $infoFile = fopen("blogs/$blog/info", 'r');
            $user = fgets($infoFile);
            $pass = fgets($infoFile);
            $desc = array();
            while(($line = fgets($infoFile)) !== FALSE){
                array_push($desc, $line);
            }
            fclose($infoFile);
            //Print name/description about each blog
            echo "<li class=\"blogItem\">";
            echo "<a href=\"blog.php?nazwa=$blog\" class=\"blogName\">$blog</a>";
            echo "<p class=\"blogDescription\">";
            foreach($desc as $line){
                echo "$line<br />";
            }
            echo "</p></li>";
        }
        echo "</ul>";

        sem_release($sem);
        sem_remove($sem);
    } 

    //Look for queried blog
    else {
        $sem = sem_get(1);
        sem_acquire($sem);
        $blogs = opendir('blogs');
        $found = FALSE;
        while(($blog = readdir($blogs)) !== FALSE && !$found){
            if($blog === '.' || $blog === '..'){
                continue;
            }
            if(is_dir("blogs/$blog") && $blog === $_GET['nazwa']){
                $found = TRUE;
            }
        }
        closedir($blogs);
        sem_release($sem);

        //Queried blog was not found
        if(!$found){
            echo "<div style=\"text-align:center;\">";
            echo "<p>Blog którego szukałeś/aś nie został znaneziony.</p>";
            echo "<a href=\"blog.php\">Powrót</a>";
            echo "</div>";
        }

        //Found a blog to display
        else{
            //Find all entries:
            sem_acquire($sem);
            $blogName = $_GET['nazwa'];
            $blogDir = opendir("blogs/$blogName");
            $entries = array();
            while(($entry = readdir($blogDir)) !== FALSE){
                if($entry === '.' ||                 // Ignore:
                    $entry === '..' ||               // '.' and '..' - unix dirs
                    $entry === 'info' ||             // 'info' - file
                    strpos($entry, '.') !== FALSE){  // '*.*' - comment folders and attachment files
                    continue;
                }
                array_push($entries, $entry);
            }
            //Sort entries in chronological order;
            sort($entries, SORT_NUMERIC);
            //Print "no entries message"
            if(count($entries) == 0){
                echo "<div style=\"text-align:center;\">";
                echo "<p>W tym blogu nie ma jeszcze żadnych postów</p>";
                echo "<p style=\"margin-bottom:0;\">Jeżeli jesteś jego autorem może pomyśl o dodaniu jakiegoś</p>";
                echo "<p style=\"margin-top:0;display:inline-block;transform:rotate(90deg)\">:)</p><br/>";
                echo "<a href=\"wpis.php\">Dodaj wpis</a><br/><br/>";
                echo "<a href=\"blog.php\">Lista blogów</a>";
                echo "</div>";
            }
            //Print the entries
            else {
                foreach($entries as $entry){
                    //Load entry file information
                    $entryFile = fopen("blogs/$blogName/$entry", 'r');
                    $entryContent = array();
                    while(($line = fgets($entryFile)) !== FALSE){
                        array_push($entryContent, $line);
                    }
                    fclose($entryFile);
    
                    echo "<div id=\"$entry\" class=\"entryDiv\">";
                    //Print date of the entry
                    $Y = substr($entry, 0, 4);
                    $M = substr($entry, 4, 2);
                    $D = substr($entry, 6, 2);
                    $G = substr($entry, 8, 2);
                    $m = substr($entry, 10, 2);
                    $S = substr($entry, 12, 2);
                    echo "<p class=\"entryDate\">$Y-$M-$D $G:$m:$S</p>";
                    
                    //Print the content array
                    echo "<p class=\"entryContent\">";
                    foreach($entryContent as $line){
                        echo "$line<br />";
                    }
                    echo "</p>";
    
                    //Print attachments
                    $attachments = array();
                    rewinddir($blogDir);
                    while(($fileName = readdir($blogDir)) !== FALSE){
                        if($entry === '.' || $entry === '..'){
                            continue;
                        }
                        if( explode('.', $fileName)[0] === $entry.'0' ||
                            explode('.', $fileName)[0] === $entry.'1' ||
                            explode('.', $fileName)[0] === $entry.'2' ){
                                array_push($attachments, $fileName);
                        }
                    }
                    if(count($attachments) > 0){
                        echo "<p>Załączniki:</p>";
                        foreach($attachments as $fileName){
                            echo "<a class=\"attachment\" href=\"blogs/$blogName/$fileName\">$fileName</a><br />";
                        }
                    }
    
                    //Print the comment link
                    echo "<a href=\"koment.php?entryID=$blogName-$entry\">Skomentuj</a><br />";
    
                    //Check if there's any comments, if so print comments section
                    if(file_exists("blogs/$blogName/$entry.k")){
                        echo "<label for=\"showComments$entry\">Pokaż komentarze</label>";
                        echo "<input class=\"toggleCommentsSection\" id=\"showComments$entry\"type=\"checkbox\" />";
                        echo "<div class=\"commentsSectionDiv\">";
    
                        //0. Get the number of comments
                        $commentDir = opendir("blogs/$blogName/$entry.k");
                        $commentCount = 0;
                        while(($comment = readdir($commentDir)) !== FALSE){
                            if($comment === '.' || $comment === '..'){
                                continue;
                            }
                            $commentCount += 1;
                        }
                        closedir($commentDir);
    
                        // Get the comments in order based on the count
                        for($i = 0; $i < $commentCount; $i++){
                            //Load comment information
                            $commentFile = fopen("blogs/$blogName/$entry.k/$i", 'r');
                            $commentType = trim(fgets($commentFile));
                            $commentDate = trim(fgets($commentFile));
                            $commentAuthor = trim(fgets($commentFile));
                            $commentContent = array();
                            while(($line = fgets($commentFile)) !== FALSE){
                                array_push($commentContent, $line);
                            }
                            fclose($commentFile);
    
                            //Print comment
                            echo "<div class=\"commentDiv\"><p>";
                            echo "<span class=\"authorNick\">$commentAuthor</span> skomentował: ";
                            echo "(<span type=\"$commentType\"></span> | $commentDate)</p>";
                            echo "<p>";
                            foreach($commentContent as $line){
                                echo "$line<br/>";
                            }
                            echo "</p></div>";
                        }
                        echo "</div>"; //End of commentsSectionDiv
                    }
                    echo "</div>"; //End of entryDiv
                }
            }
            closedir($blogDir);
            sem_release($sem);
            sem_remove($sem);
        }
    } ?>
    </div>
</body>

</html>