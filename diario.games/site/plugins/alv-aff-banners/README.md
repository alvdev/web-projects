# alv-aff-banners

Affiliate banner plugin for diario.games with visual Panel configuration.

## Setup

1. Go to the Kirby Panel → Site → Affiliate Banners
2. Toggle "Enable Affiliate Banners" on
3. Add affiliate programs (each with its own frequency):
   - **Instant Gaming**: set type to "Instant Gaming" and enter your `igr` referral ID
   - **Generic**: set type to "Generic Link" and enter a full affiliate URL

## Panel Fields

| Field | Description |
|-------|-------------|
| **Enable Affiliate Banners** | Master toggle for all banners |
| **Affiliate Programs** | Structure field with program entries |

### Program Entry Fields

| Field | Description |
|-------|-------------|
| **Program Name** | Display name (e.g. "Instant Gaming") |
| **Enabled** | Toggle per program |
| **Position** | After how many items the banner appears (sm:1/md:2/xl:4, sm:2/md:4/xl:8, sm:3/md:6/xl:16, sm:4/md:8/xl:32) |
| **Type** | `instant-gaming` (IG Banner API) or `generic` (link) |
| **Affiliate ID / Link** | For IG: your `igr` ID. For generic: full URL |
| **Banner Label** | Header text (default: "Ofertas destacadas") |
| **Sponsor Text** | Subtitle text (default: "Patrocinado") |

## How It Works

- **Per-program frequency**: Each program has its own frequency setting. The template inserts banners at the most frequent interval (lowest number), and the snippet filters which programs to show based on their individual frequency.
- **Instant Gaming**: Uses the [Partner Banner API](https://www.instant-gaming.com/en/docs/banners/) — auto-generates rotating featured-game deals with your affiliate link
- **Generic**: Renders a clickable banner linking to your affiliate URL
- Multiple programs render stacked in a single banner section
- The IG loader script is emitted only once per page, even with multiple banners

## Usage in Templates

```php
<?php snippet('affiliate-banner') ?>
```

Or as a grid item (no breakout margins):

```php
<?php snippet('affiliate-banner', ['grid' => true]) ?>
```
