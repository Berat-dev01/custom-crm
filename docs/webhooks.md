# Webhooks

CRM olaylarını dış sistemlere imzalı HTTP POST istekleri olarak iletir. Yönetim ekranı: **CRM > System > Webhooks** (`/admin/crm/webhooks`).

## Desteklenen Olaylar

| Olay | Tetiklenme |
|---|---|
| `contact.created` | Yeni kişi oluşturulduğunda |
| `company.created` | Yeni şirket oluşturulduğunda |
| `deal.created` | Yeni fırsat oluşturulduğunda |
| `deal.won` | Fırsat kazanıldı durumuna geçtiğinde |
| `deal.lost` | Fırsat kaybedildi durumuna geçtiğinde |
| `quote.sent` | Teklif gönderildi olarak işaretlendiğinde |
| `quote.accepted` | Teklif kabul edildiğinde |
| `quote.rejected` | Teklif reddedildiğinde |
| `task.completed` | Görev tamamlandığında |

## İstek Formatı

```
POST {webhook url}
Content-Type: application/json
X-CRM-Event: deal.won
X-CRM-Delivery: 6f9c1a3e-... (teslimatın benzersiz id'si)
X-CRM-Signature: hex HMAC-SHA256
```

Gövde örneği:

```json
{
  "event": "deal.won",
  "triggered_at": "2026-07-03T14:21:09+00:00",
  "data": {
    "type": "deal",
    "id": 42,
    "title": "Yıllık lisans",
    "status": "won",
    "value": 25000.0,
    "stage_id": 5,
    "company_id": 7,
    "contact_id": 12,
    "owner_id": 3,
    "closed_at": "2026-07-03T14:21:08+00:00",
    "lost_reason": null
  }
}
```

## İmza Doğrulama

Her webhook'un `whsec_` önekli bir imza anahtarı vardır (oluşturma sırasında yalnızca bir kez gösterilir). İstek gövdesinin HMAC-SHA256 özeti `X-CRM-Signature` başlığında gönderilir:

```php
$expected = hash_hmac('sha256', $rawBody, $secret);
abort_unless(hash_equals($expected, $request->header('X-CRM-Signature')), 401);
```

## Teslimat ve Retry

- Teslimatlar queue üzerinden gönderilir (production'da queue worker şart).
- 2xx dışı yanıt veya bağlantı hatasında 3 deneme yapılır (30 sn, 2 dk, 10 dk aralıklarla).
- Son 20 teslimat, durum ve HTTP kodu ile Webhooks ekranında görünür.
- Duraklatılan webhook'a teslimat yapılmaz.

## Zapier / Make.com

Her iki platformda da "Webhooks by Zapier" / "Webhook" trigger'ı ile Catch Hook URL'sini alın, CRM'de bu URL'ye istediğiniz olaylara abone bir webhook oluşturun. İmza doğrulaması opsiyoneldir; yapmak isterseniz yukarıdaki HMAC şemasını kullanın.
