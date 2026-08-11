"""Shared keyword matching for taxonomy rules (mirrors App\\Support\\KeywordMatch).

Short tokens (≤3 chars, no space) use letter-boundaries so les ≠ sales and
idi ≠ pendidikan. Longer tokens and phrases stay substring matches (ngopi still
matches kopi).
"""

from __future__ import annotations

import re


def keyword_in(haystack: str, needle: str) -> bool:
    haystack = haystack.lower()
    token = needle.lower().strip()
    if not token:
        return False
    if " " in token or len(token) > 3:
        return token in haystack
    return bool(re.search(rf"(?<![a-z_]){re.escape(token)}(?![a-z_])", haystack))


def any_keyword(haystack: str, needles: tuple[str, ...] | list[str]) -> bool:
    return any(keyword_in(haystack, n) for n in needles)
