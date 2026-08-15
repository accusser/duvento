<x-install-layout>
    <h1>Duvento установлен</h1>
    <p class="lead">Система установлена без демонстрационных данных. Сохраните адрес админки.</p>

    <div class="check">
        <span>Панель пользователя<small>{{ $appUrl }}/login</small></span>
        <a class="button" href="{{ $appUrl }}/login">Открыть</a>
    </div>
    <div class="check">
        <span>Системная админка<small>{{ $adminUrl }}</small></span>
        <a class="button" href="{{ $adminUrl }}">Открыть</a>
    </div>
    <div class="check">
        <span>Администратор</span>
        <strong>{{ $email }}</strong>
    </div>

    <h2 style="margin-top:28px">Настройте cron</h2>
    <p class="lead">Добавьте эту команду в планировщик сервера:</p>
    <code>{{ $cron }}</code>
</x-install-layout>
