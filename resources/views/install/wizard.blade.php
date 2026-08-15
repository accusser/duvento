<x-install-layout :step="$step">
    @if($errors->any())
        <div class="errors">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    @if($step === 'locale')
        <h1>Добро пожаловать</h1>
        <p class="lead">Выберите язык системы. Его можно изменить позже.</p>
        <form method="post" action="{{ route('install.locale') }}">
            @csrf
            <label>Язык
                <select name="locale">
                    @foreach($locales as $code => $locale)
                        <option value="{{ $code }}" @selected($code === 'ru')>{{ $locale['flag'] }} {{ $locale['name'] }}</option>
                    @endforeach
                </select>
            </label>
            <div class="actions"><button>Продолжить</button></div>
        </form>
    @elseif($step === 'environment')
        <h1>Проверка сервера</h1>
        <p class="lead">Duvento проверит версию PHP, расширения и права доступа.</p>
        @foreach($checks['checks'] as $check)
            <div class="check">
                <span>{{ $check['label'] }}<small>{{ $check['detail'] }}</small></span>
                <span class="{{ $check['ok'] ? 'ok' : 'bad' }}">{{ $check['ok'] ? 'Готово' : 'Ошибка' }}</span>
            </div>
        @endforeach
        <form method="post" action="{{ route('install.environment') }}">
            @csrf
            <div class="actions"><button @disabled(!$checks['ok'])>Продолжить</button></div>
        </form>
    @elseif($step === 'database')
        <h1>Подключение к базе</h1>
        <p class="lead">Укажите чистую базу MySQL/MariaDB или используйте SQLite.</p>
        <form method="post" action="{{ route('install.database') }}" id="database-form">
            @csrf
            <div class="choice">
                <label><input type="radio" name="connection" value="mysql" @checked(old('connection','mysql') === 'mysql')> MySQL / MariaDB</label>
                <label><input type="radio" name="connection" value="sqlite" @checked(old('connection') === 'sqlite')> SQLite</label>
            </div>
            <div id="mysql-fields">
                <div class="grid">
                    <label>Хост<input name="host" value="{{ old('host','127.0.0.1') }}"></label>
                    <label>Порт<input name="port" type="number" value="{{ old('port','3306') }}"></label>
                </div>
                <label>Имя базы<input name="database" value="{{ old('database') }}" autocomplete="off"></label>
                <div class="grid">
                    <label>Пользователь<input name="username" value="{{ old('username') }}" autocomplete="off"></label>
                    <label>Пароль<input name="password" type="password" autocomplete="new-password"></label>
                </div>
            </div>
            <div class="actions"><button>Проверить и сохранить</button></div>
        </form>
        <script>
            const toggleDb = () => document.getElementById('mysql-fields').hidden =
                document.querySelector('input[name=connection]:checked').value === 'sqlite';
            document.querySelectorAll('input[name=connection]').forEach(el => el.addEventListener('change', toggleDb));
            toggleDb();
        </script>
    @elseif($step === 'migrate')
        <h1>Создание таблиц</h1>
        <p class="lead">Будет создана чистая структура Duvento. Демонстрационные данные не добавляются.</p>
        <form method="post" action="{{ route('install.migrate') }}">
            @csrf
            <div class="actions"><button>Установить структуру</button></div>
        </form>
    @elseif($step === 'admin')
        <h1>Администратор</h1>
        <p class="lead">Эта учётная запись получит доступ к панели и системной админке.</p>
        <form method="post" action="{{ route('install.admin') }}">
            @csrf
            <div class="grid">
                <label>Имя<input name="name" value="{{ old('name') }}" required autocomplete="name"></label>
                <label>Email<input name="email" type="email" value="{{ old('email') }}" required autocomplete="email"></label>
            </div>
            <label>Название рабочего пространства<input name="workspace" value="{{ old('workspace','Моя компания') }}" required></label>
            <div class="grid">
                <label>Пароль<input name="password" type="password" required autocomplete="new-password"><small>Минимум 10 символов, буквы и цифры.</small></label>
                <label>Повторите пароль<input name="password_confirmation" type="password" required autocomplete="new-password"></label>
            </div>
            <label>Адрес системной админки
                <div style="display:flex;gap:9px">
                    <input id="admin-path" name="admin_path" value="{{ old('admin_path',$adminPath) }}" required pattern="[a-z0-9][a-z0-9_-]{1,31}">
                    <button class="secondary" type="button" id="generate-path">Сгенерировать</button>
                </div>
                <small>{{ url('/') }}/<span id="path-preview">{{ old('admin_path',$adminPath) }}</span></small>
            </label>
            <div class="actions"><button>Завершить установку</button></div>
        </form>
        <script>
            const field = document.getElementById('admin-path');
            const preview = document.getElementById('path-preview');
            field.addEventListener('input', () => preview.textContent = field.value);
            document.getElementById('generate-path').addEventListener('click', async () => {
                const bytes = new Uint8Array(6); crypto.getRandomValues(bytes);
                field.value = 'adm-' + Array.from(bytes, b => (b % 36).toString(36)).join('');
                field.dispatchEvent(new Event('input'));
            });
        </script>
    @endif
</x-install-layout>
