<?php

namespace Helpers;

class Message
{
    /** Idiomas con catalogo en config/lang/. Evita armar rutas con datos externos. */
    private const SUPPORTED = ['es', 'en'];
    private const FALLBACK = 'es';

    /** @var array<string, array<string, string>> catalogos ya cargados, por idioma */
    private static $catalogs = [];

    /**
     * Cachea por idioma. Antes se cacheaba un unico catalogo global, asi que el
     * primer mensaje del request fijaba el idioma: un usuario en 'en' recibia
     * español si algo habia respondido antes de autenticar.
     */
    private static function loadMessages(string $lang): array
    {
        if (!isset(self::$catalogs[$lang])) {
            self::$catalogs[$lang] = require __DIR__ . "/../config/lang/{$lang}.php";
        }

        return self::$catalogs[$lang];
    }

    /**
     * Normaliza contra la lista de idiomas soportados: el valor viene del claim
     * 'lang' del JWT y nunca debe usarse tal cual para armar una ruta de archivo.
     */
    private static function currentLang(): string
    {
        $lang = strtolower(trim((string)($_SERVER['user']['lang'] ?? self::FALLBACK)));

        return in_array($lang, self::SUPPORTED, true) ? $lang : self::FALLBACK;
    }

    public static function get($key)
    {
        // Clave nula (respuestas que solo devuelven datos): no hay nada que traducir.
        if ($key === null || $key === '') {
            return null;
        }

        $lang = self::currentLang();
        $messages = self::loadMessages($lang);
        if (isset($messages[$key])) {
            return $messages[$key];
        }

        // Catalogo incompleto: antes se devolvia la clave cruda y el usuario veia
        // "STOCK_UPDATED" como mensaje. Se cae al idioma base y, si tampoco esta,
        // se avisa por log para que la falta se pueda corregir.
        if ($lang !== self::FALLBACK) {
            $base = self::loadMessages(self::FALLBACK);
            if (isset($base[$key])) {
                return $base[$key];
            }
        }

        Logger::warning('Clave de mensaje sin traduccion', ['key' => $key, 'lang' => $lang]);

        return $key;
    }
}
