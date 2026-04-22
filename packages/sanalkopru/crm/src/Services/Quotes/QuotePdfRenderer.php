<?php

namespace Sanalkopru\Crm\Services\Quotes;

use Dompdf\Dompdf;
use Dompdf\Options;
use Sanalkopru\Crm\Models\Quote;

class QuotePdfRenderer
{
    public function render(Quote $quote): string
    {
        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('crm::admin.quotes.pdf', [
            'quote' => $quote,
        ])->render(), 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();

        return $dompdf->output();
    }
}
