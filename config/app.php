<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Configuración general de la aplicación
|--------------------------------------------------------------------------
| Puede cambiar el nombre del consultorio, el símbolo de moneda y la zona
| horaria sin tocar el resto del código.
*/

const APP_NAME       = 'Consultorio Odontológico';
const APP_SHORT      = 'OdontoClínica';
const APP_SUBTITLE   = 'Historia clínica dental';
const APP_DOCTOR     = 'Dr. Juan Medina';
const MONEDA         = '$';                 // Símbolo de moneda mostrado.
const ZONA_HORARIA   = 'America/Caracas';   // Ajuste según su país.

date_default_timezone_set(ZONA_HORARIA);
