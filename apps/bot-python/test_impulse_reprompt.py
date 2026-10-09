"""Wiring bot.py untuk pertanyaan terencana: copy yang dikirim dan re-prompt balasan teks."""

from __future__ import annotations

import asyncio
import unittest

import bot


class _FakeMessage:
    def __init__(self) -> None:
        self.replies: list[str] = []

    async def reply_text(self, text: str, **_kwargs) -> None:
        self.replies.append(text)


def _makan(nominal: int = 120_000) -> dict:
    return {
        "jenis": "Pengeluaran",
        "kategori": "Makanan & Minuman",
        "sifat": "Need",
        "nominal": nominal,
        "keterangan": "Makan malam",
    }


class ImpulseRepromptTests(unittest.TestCase):
    USER_ID = 424242

    def tearDown(self) -> None:
        bot.PENDING_IMPULSE_WAIT.pop(self.USER_ID, None)

    def _queue(self, parsed: dict, source_text: str) -> _FakeMessage:
        message = _FakeMessage()
        asyncio.run(
            bot.queue_impulse_clarification(
                message, self.USER_ID, parsed, "Steph", source_text=source_text
            )
        )
        return message

    def test_makan_sends_eating_out_copy(self) -> None:
        message = self._queue(_makan(), "makan malam 120rb")
        self.assertEqual(
            message.replies,
            ["Transaksi sudah kebaca. Aku perlu memastikan dulu:\n\nApakah ini *terencana*?"],
        )

    def test_unrecognised_text_reply_reprompts_same_question(self) -> None:
        # Dulu: TypeError (prompt= hilang, from_receipt= tidak diterima).
        self._queue(_makan(), "makan malam 120rb")
        message = _FakeMessage()
        asyncio.run(bot.reprompt_impulse_clarification(message, self.USER_ID))
        self.assertEqual(len(message.replies), 1)
        self.assertIn("Apakah ini *terencana*?", message.replies[0])
        self.assertIn(self.USER_ID, bot.PENDING_IMPULSE_WAIT)


if __name__ == "__main__":
    unittest.main()
