<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

class DocumentoPdfService
{
    public function generar(string $vista, array $datos, bool $horizontal = false): string
    {
        gc_collect_cycles();
        $limiteAnterior = ini_get('memory_limit');
        $limitePdf = (string) config('academico.pdf_memoria', '256M');
        $ajustado = $limiteAnterior !== '-1' && $this->bytes($limiteAnterior) < $this->bytes($limitePdf);
        if ($ajustado) {
            ini_set('memory_limit', $limitePdf);
        }
        try {
            $opciones = new Options;
            $opciones->set('isRemoteEnabled', false);
            $opciones->set('isPhpEnabled', false);
            $opciones->set('isJavascriptEnabled', false);
            $opciones->set('defaultFont', 'DejaVu Sans');
            $opciones->set('chroot', resource_path('views'));
            $pdf = new Dompdf($opciones);
            $pdf->loadHtml(view($vista, $datos)->render(), 'UTF-8');
            $pdf->setPaper($horizontal ? [0, 0, 612, 1008] : 'A4', $horizontal ? 'landscape' : 'portrait');
            $pdf->render();

            return $pdf->output();
        } finally {
            unset($pdf);
            gc_collect_cycles();
            if ($ajustado && memory_get_usage(true) < $this->bytes($limiteAnterior)) {
                ini_set('memory_limit', $limiteAnterior);
            }
        }
    }

    private function bytes(string $valor): int
    {
        $valor = trim($valor);
        $multiplicador = match (strtoupper(substr($valor, -1))) {
            'G' => 1024 ** 3,
            'M' => 1024 ** 2,
            'K' => 1024,
            default => 1,
        };

        return (int) $valor * $multiplicador;
    }
}
