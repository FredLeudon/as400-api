<?php
declare(strict_types=1);

namespace App\Domain;

final class Company
{
    private const BY_NAME = [
        'matfer' => '06',
        'insitu' => '40',
        'bourgeat' => '38',
        'flovending' => '15',
        'matik' => '69',
        'mbc' => '69',
        'mbhe' => '19',
        'mbhea' => '19',
        'calle' => '31',
        'sogemat' => '91',
        'tecmat' => '96',
        'vauconsant' => '54',
    ];

    private const BY_CODE = [
        ''   => [ 'name' => 'MBI',                          'library' => 'FCMBI',      'common_library' => 'FCMBI',     'main_library' => 'MATIS',      'code' => '00',     'common_code' => '00',    'mbi' => true   ],
        '00' => [ 'name' => 'MBI',                          'library' => 'FCMBI',      'common_library' => 'FCMBI',     'main_library' => 'MATIS',      'code' => '00',     'common_code' => '00',    'mbi' => true   ],
        '06' => [ 'name' => 'Matfer',                       'library' => 'MATFER',     'common_library' => 'FCMBI',     'main_library' => 'MATIS',      'code' => '06',     'common_code' => '00',    'mbi' => true   ],
        '38' => [ 'name' => 'Bourgeat',                     'library' => 'BOURGEAT',   'common_library' => 'FCMBI',     'main_library' => 'MATIS',      'code' => '38',     'common_code' => '00',    'mbi' => true   ],
        '40' => [ 'name' => 'In-Situ',                      'library' => 'INSITU',     'common_library' => 'FCMBI',     'main_library' => 'MATIS',      'code' => '40',     'common_code' => '00',    'mbi' => true   ],
        '15' => [ 'name' => 'Flo-Vending',                  'library' => 'FLOVENDING', 'common_library' => 'FLOVENDING','main_library' => 'FLOVENDING', 'code' => '15',     'common_code' => '15',    'mbi' => false  ],
        '69' => [ 'name' => 'Matfer-Bourgeat Corporate',    'library' => 'MATIK',      'common_library' => 'MATIK',     'main_library' => 'MATIS',      'code' => '69',     'common_code' => '69',    'mbi' => false  ],
        '91' => [ 'name' => 'Sogémat',                      'library' => 'SOGEMAT',    'common_library' => 'SOGEMAT',   'main_library' => 'MATIS',      'code' => '91',     'common_code' => '91',    'mbi' => false  ],
        '31' => [ 'name' => 'Ets André Calle',              'library' => 'CALLE',      'common_library' => 'CALLE',     'main_library' => 'MATIS',      'code' => '31',     'common_code' => '31',    'mbi' => false  ],
        '96' => [ 'name' => 'Tec-Mat',                      'library' => 'TECMAT',     'common_library' => 'TECMAT',    'main_library' => 'MATIS',      'code' => '96',     'common_code' => '96',    'mbi' => false  ],
        '19' => [ 'name' => 'MBHE-A',                       'library' => 'MBHE',       'common_library' => 'MBHE',      'main_library' => 'MATIS',      'code' => '19',     'common_code' => '19',    'mbi' => false  ],
        '54' => [ 'name' => 'Vauconsant',                   'library' => 'VAUCONSANT', 'common_library' => 'FCMBI',     'main_library' => 'VAUCONSANT', 'code' => '54',     'common_code' => '54',    'mbi' => false  ],
    ];

    public static function codeOf(string $company): ?string
    {
        $key = strtolower(trim($company));

        if (isset(self::BY_NAME[$key])) return self::BY_NAME[$key];
        if (isset(self::BY_CODE[$key])) return self::BY_CODE[$key]['code'];

        return null;
    }

    public static function get(string $companyOrCode): ?array
    {
        $key = strtolower(trim($companyOrCode));

        if (isset(self::BY_NAME[$key])) {
            $code = self::BY_NAME[$key];
            return self::BY_CODE[$code] ?? null;
        }
        if (isset(self::BY_CODE[$key])) {
            return self::BY_CODE[$key];
        }
        return null;
    }

    public static function all(): array
    {
        return array_values(self::BY_CODE);
    }
}
