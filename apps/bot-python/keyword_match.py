"""Shared keyword matching for taxonomy rules (mirrors App\\Support\\KeywordMatch).

Short tokens (≤3 chars, no space) use letter-boundaries so les ≠ sales and
idi ≠ pendidikan. Longer tokens and phrases stay substring matches (ngopi still
matches kopi).

Some 4+ letter food tokens still need boundaries (nasi ⊂ donasi, spa ⊂ spaghetti
handled elsewhere).
"""

from __future__ import annotations

import re

# Substring berbahaya di kata Indonesia lain — selalu pakai word boundary.
_ALWAYS_WORD_BOUNDARY = frozenset(
    {
        "nasi",  # ⊂ donasi, organisasi, …
        "soto",
        "mie",
        "air",  # ⊂ fair, airbnb — air minum dicek terpisah; boundary lebih aman
    }
)


def keyword_in(haystack: str, needle: str) -> bool:
    haystack = haystack.lower()
    token = needle.lower().strip()
    if not token:
        return False
    if " " in token:
        return token in haystack
    if token in _ALWAYS_WORD_BOUNDARY or len(token) <= 3:
        return bool(re.search(rf"(?<![a-z_]){re.escape(token)}(?![a-z_])", haystack))
    return token in haystack


def any_keyword(haystack: str, needles: tuple[str, ...] | list[str]) -> bool:
    return any(keyword_in(haystack, n) for n in needles)
