#!/usr/bin/env python3
"""Parse AWT "Referring domains" print-to-PDF exports into a domain report + GSC disavow file.

Usage: python3 seo/backlinks/parse-awt-refdomains.py
Requires ghostscript (gs) for PDF text extraction with coordinates.
"""
import csv
import glob
import re
import subprocess
import sys
import xml.etree.ElementTree as ET
from collections import Counter
from pathlib import Path

HERE = Path(__file__).resolve().parent
MONTHS = "Jan Feb Mar Apr May Jun Jul Aug Sep Oct Nov Dec".split()
MONTH_NUM = {m: i + 1 for i, m in enumerate(MONTHS)}

DATE_RE = re.compile(r"(\d{1,2} (?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec) \d{4})")
FRAG_RE = re.compile(r"^-?[a-z0-9][a-z0-9.-]*$")
TLD_RE = re.compile(
    r"\.(com|es|net|org|io|sh|shop|store|website|online|space|site|xyz|link|top|info|biz|pro|co|uk|eu|de|fr|it|pt|app|dev|cloud|tech|digital|agency|art|blog|club|live|media|news|ai|lc|ws|cv|party|fyi|info|place|cc|in|icu|wiki)$"
)
BAD_WORDS = {
    "spam", "to", "use", "how", "all", "new", "lost", "dr", "domain", "domains", "first",
    "seen", "export", "settings", "presets", "columns", "history", "word", "phrase", "name",
    "add", "filter", "referring", "dofollow", "nofollow", "ugc", "traffic", "keywords",
    "links", "target", "best", "off", "show", "api",
}
SPAM_TLD_RE = re.compile(
    r"\.(shop|store|website|online|space|xyz|link|top|site|icu|click|sh|rest|buzz|monster|quest|bar|uno|sbs|cfd|cyou|party|fyi|ws|cv)$"
)
SPAM_NAME_RE = re.compile(
    r"(seo|backlink|link[-.]|rank|outrank|guest[-]?post|traffic|boost|checker|dapa|serp|dofollow|pbn|hub|wiki|directory|search-?engine|indexer|website|domain-?(check|audit|analysis))",
    re.I,
)

SELF = {"urbanstylepublicity.com", "www.urbanstylepublicity.com"}
KEEP = {
    "elnegociodelamusica.com",
    "gowork.com",
    "nomas900.org",
    "funcionando.com",
    "onebusiness.place",
}
MANUAL_DISAVOW = {
    "topbillion.net",
    "agenciaazafatas.com",
    "fastlifesite.com",
    "preparation.co.in",
    "domain.com.lc",
    "mylisthero.com",
    "uncledspizza.com",
    "ready.pro",
    "ycm.info",
    "theforbestimes.com",
    "wonvision.com",
    "bestwebstats.com",
    "blogerreviewers.com",
    "dupurgeniefr.com",
    "sahammurah.com",
    "itsyourgold.com",
    "indexaward.com",
    "alljobs.info",
    "domainsc.com",
    "goooogla.com",
    "portaldelamusicaenvivo.com",
    "portailorange.net",
    "archive-hu.com",
    "findit.co.in",
    "ebuzoneo.com",
    "jake.eu",
    "nexusnext.agency",
    "destaco.es",
    "allinone.co.in",
    "wallpapers.pro",
    "addurl.in",
    "tunca.org",
    "indians.cc",
    "betulcrime.com",
    "trendyhealthtimes.com",
    "primenewsartical.com",
    "sorkapp.com",
    "pagesearch.net",
    "publiazafatas.com",
    "techbumppy.com",
    "fashionclothingnews.com",
    "canadapsilocybin.com",
    "digitalchatni.com",
    "cgpa2percentag.com",
    "s-tribe.net",
    "starcourts.com",
    "verificascore.com",
    "cartermanageus.com",
    "kkinsider.com",
    "prospeo.io",
    "findinfo.in",
    "wecelebrities.com",
    "tyre.pro",
    "1000.co.in",
    "homesforsaleoldgreenwichct.com",
    "cvillico.com",
    "acquire.co.in",
    "globalecommerce.org",
    "gamerkun.com",
    "quotesblom.com",
    "bestnz-poker-casinoslot.com",
    "homefinance.co.in",
    "aloysionunes.com",
    "sapphirevpn.net",
}


def extract_pages(pdf: Path) -> list[list[tuple[float, list[tuple[float, str]]]]]:
    out = subprocess.run(
        ["gs", "-q", "-dNOPAUSE", "-dBATCH", "-sDEVICE=txtwrite", "-dTextFormat=1", "-sOutputFile=-", str(pdf)],
        capture_output=True,
    ).stdout
    root = ET.fromstring(b"<root>" + out + b"</root>")
    pages: list[list[tuple[float, list[tuple[float, str]]]]] = []
    for page in root:
        spans: list[tuple[float, float, str]] = []
        for span in page.iter("span"):
            bbox = span.get("bbox")
            if not bbox:
                continue
            x1, y1, x2, y2 = map(float, bbox.split())
            text = "".join(ch.get("c", "") for ch in span.iter("char"))
            if text.strip():
                spans.append((round((y1 + y2) / 2, 1), x1, text))
        spans.sort()
        lines: list[tuple[float, list[tuple[float, str]]]] = []
        for y, x, text in spans:
            if lines and abs(lines[-1][0] - y) <= 2.5:
                lines[-1][1].append((x, text))
            else:
                lines.append((y, [(x, text)]))
        pages.append(lines)
    return pages


def parse_pdf(pdf: Path) -> list[dict]:
    rows: list[dict] = []
    for lines in extract_pages(pdf):
        current: dict | None = None
        for _y, items in lines:
            items.sort()
            line_text = " ".join(t for _, t in items)
            frags = [
                t
                for x, t in items
                if 40 < x < 190 and FRAG_RE.match(t) and t not in BAD_WORDS and any(c.isalpha() for c in t)
            ]
            badge = any(t == "SPAM" for _, t in items)
            date_m = DATE_RE.search(line_text)
            if date_m:
                current = {"domain": "".join(frags), "date": date_m.group(1), "badge": "SPAM" if badge else ""}
                rows.append(current)
            elif current is not None:
                if frags:
                    current["domain"] += "".join(frags)
                if badge and not current["badge"]:
                    current["badge"] = "SPAM"
    return rows


def classify(domain: str) -> str:
    if domain in SELF:
        return "self"
    if domain in KEEP:
        return "keep"
    if domain in MANUAL_DISAVOW:
        return "spam"
    if SPAM_TLD_RE.search(domain) or SPAM_NAME_RE.search(domain):
        return "spam"
    return "review"


def month_key(date: str) -> str:
    _day, mon, year = date.split()
    return f"{year}-{MONTH_NUM[mon]:02d}"


def main() -> None:
    patterns = sys.argv[1:] or [str(HERE.parent / "reports" / "Referring domains_*sorted*.pdf")]
    pdfs = sorted({p for pat in patterns for p in glob.glob(pat)})
    if not pdfs:
        sys.exit("no PDFs found")

    seen: dict[str, dict] = {}
    all_rows: list[dict] = []
    duplicates = 0
    for pdf in pdfs:
        for r in parse_pdf(Path(pdf)):
            if r["domain"] in seen:
                duplicates += 1
                continue
            seen[r["domain"]] = r
            all_rows.append(r)

    for r in all_rows:
        r["class"] = classify(r["domain"])

    incomplete = [r for r in all_rows if not TLD_RE.search(r["domain"])]
    hist = Counter(month_key(r["date"]) for r in all_rows)

    csv_path = HERE / "referring-domains-2026-09-27.csv"
    disavow_path = HERE / "disavow.txt"

    with csv_path.open("w", newline="") as fh:
        writer = csv.writer(fh)
        writer.writerow(["domain", "first_seen", "class", "ahrefs_badge"])
        for r in sorted(all_rows, key=lambda x: x["date"], reverse=True):
            writer.writerow([r["domain"], r["date"], r["class"], r["badge"]])

    spam = sorted(r["domain"] for r in all_rows if r["class"] == "spam")
    with disavow_path.open("w") as fh:
        fh.write("# Generated 2026-09-27 from AWT referring-domains exports (negative SEO attack)\n")
        fh.write("# Upload in Google Search Console -> Disavow links\n")
        for d in spam:
            fh.write(f"domain:{d}\n")

    print(f"PDFs parsed: {len(pdfs)}")
    print(f"domains parsed: {len(all_rows)} (duplicates skipped: {duplicates})")
    print(f"spam: {len(spam)}  review: {len([r for r in all_rows if r['class'] == 'review'])}  keep: {len([r for r in all_rows if r['class'] == 'keep'])}")
    print(f"incomplete reconstructions: {len(incomplete)}")
    for r in incomplete:
        print("  ?", r["domain"], r["date"])
    print("\nfirst-seen histogram:")
    for k in sorted(hist):
        print(f"  {k}: {hist[k]}")
    print("\nreview list (not auto-disavowed):")
    for r in sorted((r for r in all_rows if r["class"] == "review"), key=lambda x: x["date"]):
        print(f"  {r['date']}  {r['domain']}  badge={r['badge'] or '-'}")
    print(f"\nwrote {csv_path}")
    print(f"wrote {disavow_path} ({len(spam)} domains)")


if __name__ == "__main__":
    main()
