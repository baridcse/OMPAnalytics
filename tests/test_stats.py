import unittest

from omp_analysis.stats import mean, median, stdev, summarize


class TestMean(unittest.TestCase):
    def test_basic(self):
        self.assertEqual(mean([2, 4, 6]), 4)

    def test_single_value(self):
        self.assertEqual(mean([9]), 9)

    def test_empty_raises(self):
        with self.assertRaises(ValueError):
            mean([])


class TestMedian(unittest.TestCase):
    def test_odd_count(self):
        self.assertEqual(median([3, 1, 2]), 2)

    def test_even_count(self):
        self.assertEqual(median([1, 2, 3, 4]), 2.5)

    def test_empty_raises(self):
        with self.assertRaises(ValueError):
            median([])


class TestStdev(unittest.TestCase):
    def test_basic(self):
        # population stdev of [2, 4, 6] -> sqrt(8/3)
        self.assertAlmostEqual(stdev([2, 4, 6]), 1.632993, places=6)

    def test_identical_values(self):
        self.assertEqual(stdev([5, 5, 5]), 0)

    def test_empty_raises(self):
        with self.assertRaises(ValueError):
            stdev([])


class TestSummarize(unittest.TestCase):
    def test_fields(self):
        stats = summarize([4, 8, 15, 16, 23, 42])
        self.assertEqual(stats["count"], 6)
        self.assertEqual(stats["mean"], 18)
        self.assertEqual(stats["median"], 15.5)
        self.assertAlmostEqual(stats["stdev"], 12.315302, places=6)
        self.assertEqual(stats["min"], 4)
        self.assertEqual(stats["max"], 42)

    def test_empty_raises(self):
        with self.assertRaises(ValueError):
            summarize([])


if __name__ == "__main__":
    unittest.main()
