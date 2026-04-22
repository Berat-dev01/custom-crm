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

        return 'teklif-'.$safeNumber.'.pdf';
    }

    /**
     * @return array<string, string|null>
     */
    public function companyProfile(): array
    {
        return [
            'name' => config('crm.quotes.company.name') ?: config('app.name', 'CRM'),
            'address' => config('crm.quotes.company.address'),
            'phone' => config('crm.quotes.company.phone'),
            'email' => config('crm.quotes.company.email'),
            'website' => config('crm.quotes.company.website'),
            'tax_office' => config('crm.quotes.company.tax_office'),
            'tax_number' => config('crm.quotes.company.tax_number'),
        ];
    }

    public function logoPath(): ?string
    {
        $path = config('crm.quotes.company.logo_path');

        if (! is_string($path) || $path === '') {
            return null;
        }

        $absolutePath = str_starts_with($path, '/') ? $path : public_path($path);

        return is_file($absolutePath) ? $absolutePath : null;
    }
}
