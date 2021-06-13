<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>パスワードリマインダー</title>
    </head>
    <body>
        <div class="content">
            <h1>パスワードリマインダー</h1>
            <form action="/password_change" method="POST">
                {{ csrf_field() }}

                <input type="hidden" name="token" value="{{$token}}">
                @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <table border="0">
                    <tr>
                        <th>新パスワード</th>
                        <td><input type="password" name="password" value=""></td>
                    </tr>
                    <tr>
                        <th>新パスワード再入力</th>
                        <td><input type="password" name="password_confirmation" value=""></td>
                    </tr>
                    <tr>
                        <td colspan="2"><input type="submit" value="送信"></td>
                    </tr>
                </table>
            </form>
        </div>
    </body>
</html>
