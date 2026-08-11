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

        $messages = self::loadMessages(self::currentLang());

        return $messages[$key] ?? $key;
    }
}
