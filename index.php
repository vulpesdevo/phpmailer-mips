<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Send Email</title>
    </head>

    <body>
        <form style="display: flex; flex-direction: column; align-items: center; justify-content: start;"
            action="send.php" method="post">
            Email <input type="email" name="email" value="" placeholder="Email Address">
            Subject <input type="text" name="subject" value="" placeholder="Subject">
            Message <input type="text" name="message" value="" placeholder="Message">

            <button type="submit" name="send">Send Email</button>
        </form>
    </body>

</html>