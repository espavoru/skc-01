<?php
/**
 * Основные параметры WordPress.
 *
 * Скрипт для создания wp-config.php использует этот файл в процессе
 * установки. Необязательно использовать веб-интерфейс, можно
 * скопировать файл в "wp-config.php" и заполнить значения вручную.
 *
 * Этот файл содержит следующие параметры:
 *
 * * Настройки MySQL
 * * Секретные ключи
 * * Префикс таблиц базы данных
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** Параметры MySQL: Эту информацию можно получить у вашего хостинг-провайдера ** //
/** Имя базы данных для WordPress */
define('DB_NAME', 'rubase');

/** Имя пользователя MySQL */
define('DB_USER', 'root');

/** Пароль к базе данных MySQL */
define('DB_PASSWORD', '');

/** Имя сервера MySQL */
define('DB_HOST', 'localhost');

/** Кодировка базы данных для создания таблиц. */
define('DB_CHARSET', 'utf8mb4');

/** Схема сопоставления. Не меняйте, если не уверены. */
define('DB_COLLATE', '');

/**#@+
 * Уникальные ключи и соли для аутентификации.
 *
 * Смените значение каждой константы на уникальную фразу.
 * Можно сгенерировать их с помощью {@link https://api.wordpress.org/secret-key/1.1/salt/ сервиса ключей на WordPress.org}
 * Можно изменить их, чтобы сделать существующие файлы cookies недействительными. Пользователям потребуется авторизоваться снова.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'V,$(A{Vf98}+[?&Y1imR0NTzI+9xFr&CM|8[c)_4?F=jR#K@b9]7nCKl )|H3_$B');
define('SECURE_AUTH_KEY',  '5exi&u@J$k.69yX:w49_#&HGS(wHbbDCXl.<#c$z`%2~x+lzAgB2$;-LKXOiFldZ');
define('LOGGED_IN_KEY',    '=0C/:czut!ci9cEwX]WKy1|CdVPy5hqId[TM0qLmn[(Ck|{]y>JJR lUg.~^zj)=');
define('NONCE_KEY',        '@/mB4o,sUt=8T){{$Wtu|uQ:KdI9VEFOk&zV)Sdh*7FJUG<u&9_wWRBu*10^TQu*');
define('AUTH_SALT',        '~?JPfCDf)4fpAc$Zt_<#?MOx0z{9I](64E.Jz&9*+X/S`]s[yGv`<+z[q;B]H?[d');
define('SECURE_AUTH_SALT', 'Z2sS#Adbc)Nfz{(s+.]Ry$C}/m;:s25abje`&ucqT/;o4).3WAf{p-Dd+ZK`>R5)');
define('LOGGED_IN_SALT',   '$Er^Pf2<o]2PWZjl)4`~6U_,4P%mt*=]:zHVbX%@`|Gr74%-xdN%v^eMFIDMX~4`');
define('NONCE_SALT',       '0LPEY*#IcIGc6TZ7P/gl3?Y$kr|?$12On[0jh[C6%{S;^!w2|Erez<Wy>-T/M% J');

/**#@-*/

/**
 * Префикс таблиц в базе данных WordPress.
 *
 * Можно установить несколько сайтов в одну базу данных, если использовать
 * разные префиксы. Пожалуйста, указывайте только цифры, буквы и знак подчеркивания.
 */
$table_prefix  = '01lkx_';

/**
 * Для разработчиков: Режим отладки WordPress.
 *
 * Измените это значение на true, чтобы включить отображение уведомлений при разработке.
 * Разработчикам плагинов и тем настоятельно рекомендуется использовать WP_DEBUG
 * в своём рабочем окружении.
 * 
 * Информацию о других отладочных константах можно найти в Кодексе.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define('WP_DEBUG', false);

/* Это всё, дальше не редактируем. Успехов! */

/** Абсолютный путь к директории WordPress. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Инициализирует переменные WordPress и подключает файлы. */
require_once(ABSPATH . 'wp-settings.php');
