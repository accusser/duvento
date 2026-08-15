<?php

namespace Tests\Unit;

use App\Support\SqliteUnicode;
use Tests\TestCase;

class SqliteUnicodeTest extends TestCase
{
    public function test_like_ignores_case_for_non_ascii_characters(): void
    {
        $this->assertTrue(SqliteUnicode::matches('%север%', 'Северная студия'));
        $this->assertTrue(SqliteUnicode::matches('%СЕВЕРНАЯ СТУДИЯ%', 'Северная студия'));
        $this->assertTrue(SqliteUnicode::matches('%NORD%', 'nordic-atelier.ru'));
        $this->assertFalse(SqliteUnicode::matches('%южная%', 'Северная студия'));
    }

    public function test_like_keeps_sql_wildcard_semantics(): void
    {
        $this->assertTrue(SqliteUnicode::matches('север_ая%', 'Северная студия'));
        $this->assertFalse(SqliteUnicode::matches('север', 'Северная студия'));
        $this->assertFalse(SqliteUnicode::matches('%.ru', 'nordic-atelier.rux'));
        $this->assertTrue(SqliteUnicode::matches('a!_b', 'a_b', '!'));
        $this->assertFalse(SqliteUnicode::matches('a!_b', 'axb', '!'));
    }

    public function test_like_returns_null_for_null_operands(): void
    {
        $this->assertNull(SqliteUnicode::matches('%a%', null));
        $this->assertNull(SqliteUnicode::matches(null, 'a'));
    }

    public function test_case_functions_handle_multibyte_values(): void
    {
        $this->assertSame('журнал', SqliteUnicode::lower('ЖУРНАЛ'));
        $this->assertSame('ЖУРНАЛ', SqliteUnicode::upper('журнал'));
        $this->assertNull(SqliteUnicode::lower(null));
        $this->assertNull(SqliteUnicode::upper(null));
    }
}
