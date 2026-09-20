import "./helpers/silence";
import { describe, expect, test } from "bun:test";
import { withAvailability, withAvailability503 } from "../llm";

const err503 = (): Error => new Error("503 Service Unavailable (high demand)");
const err429 = (): Error => new Error("429 Too Many Requests (quota)");

// Tiny intervals keep the bound tests fast while exercising the same code path
// as the real 30s/60s waits.
const FAST = [1, 1, 1, 1] as const;

describe("withAvailability503", () => {
  test("gives up after a bounded number of retries instead of hanging forever", async () => {
    let calls = 0;
    await expect(
      withAvailability503("test", async () => {
        calls++;
        throw err503();
      }, FAST),
    ).rejects.toThrow("503");
    expect(calls).toBe(3); // initial attempt + 2 retries
  });

  test("succeeds after a transient 503", async () => {
    let calls = 0;
    const result = await withAvailability503(
      "test",
      async () => {
        calls++;
        if (calls < 2) throw err503();
        return "ok";
      },
      FAST,
    );
    expect(result).toBe("ok");
    expect(calls).toBe(2);
  });

  test("rethrows non-503 errors immediately", async () => {
    let calls = 0;
    await expect(
      withAvailability503(
        "test",
        async () => {
          calls++;
          throw new Error("boom");
        },
        FAST,
      ),
    ).rejects.toThrow("boom");
    expect(calls).toBe(1);
  });
});

describe("withAvailability retry503 option", () => {
  test("retries 503 a bounded number of times by default", async () => {
    let calls = 0;
    await expect(
      withAvailability(
        "test",
        async () => {
          calls++;
          throw err503();
        },
        { intervals: FAST },
      ),
    ).rejects.toThrow("503");
    expect(calls).toBe(3);
  });

  test("retry503:false surfaces the 503 without retrying (Gemini already retried it)", async () => {
    let calls = 0;
    await expect(
      withAvailability(
        "test",
        async () => {
          calls++;
          throw err503();
        },
        { retry503: false, intervals: FAST },
      ),
    ).rejects.toThrow("503");
    expect(calls).toBe(1);
  });

  test("still retries 429 when retry503 is false", async () => {
    let calls = 0;
    await expect(
      withAvailability(
        "test",
        async () => {
          calls++;
          throw err429();
        },
        { retry503: false, intervals: FAST },
      ),
    ).rejects.toThrow("429");
    expect(calls).toBe(3);
  });
});
