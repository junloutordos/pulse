FROM python:3.12-slim
WORKDIR /work/pipeline
COPY pipeline/ /work/pipeline/
RUN pip install --no-cache-dir -e ".[dev]"
