<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>リマインダー</title>
    </head>
    <body>
        <div>
            @if ($result == "success")
            <div class="content">
                <h1>パスワードリマインダー</h1>
                <div>パスワードを更新しました</div>
            </div>
            @else
            <div class="content">
                <h1>パスワードリマインダー</h1>
                <div>認証コードが見つからないか、有効期限が過ぎています。</div>
            </div>
            @endif
        </>
    </body>
</html>
