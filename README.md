# OMP Analysis Tool

A small command-line tool for computing summary statistics over a series of
numeric measurements (for example, readings exported from an OMP data source).

## Installation

No third-party dependencies are required — just Python 3.9+.

```bash
git clone <your-repo-url>
cd OMPAnalysisTool
```

## Usage

Pass numbers directly:

```bash
python -m omp_analysis 4 8 15 16 23 42
```

Or pipe them in, one per line:

```bash
cat measurements.txt | python -m omp_analysis
```

Example output:

```
count:  6
mean:   18.0000
median: 15.5000
stdev:  12.3153
min:    4.0000
max:    42.0000
```

## Running the tests

```bash
python -m unittest discover -s tests
```

## License

MIT
