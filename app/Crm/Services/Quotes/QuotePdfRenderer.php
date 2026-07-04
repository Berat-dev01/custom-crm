<?php

namespace App\Crm\Services\Quotes;

use App\Crm\Models\Quote;
use App\Crm\Services\Settings\CrmSettingsManager;
use Dompdf\Dompdf;
use Dompdf\Options;

class QuotePdfRenderer
{
    public function __construct(private readonly CrmSettingsManager $settings) {}

    public function render(Quote $quote): string
    {
        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('crm::admin.quotes.pdf', [
            'quote' => $quote,
            'company' => $this->companyProfile(),
            'logoPath' => $this->logoPath(),
        ])->render(), 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();

        return $dompdf->output();
    }

    public function filename(Quote $quote): string
    {
        $safeNumber = preg_replace('/[^A-Za-z0-9._-]+/', '-', $quote->quote_number) ?: (string) $quote->id;

        return 'quote-'.$safeNumber.'.pdf';
    }

    /**
     * @return array<string, string|null>
     */
    public function companyProfile(): array
    {
        return $this->settings->companyProfile();
    }

    public function logoPath(): ?string
    {
        return $this->settings->logoPath();
    }
}
