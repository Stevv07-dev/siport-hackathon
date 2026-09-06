import json
import os

from dotenv import load_dotenv
from google import genai

from pydantic import BaseModel

from .schemas import ProductState

from typing import Optional


class UpdatedFields(BaseModel):
    product_type: Optional[str] = None

    connector_present: Optional[bool] = None
    connector_type: Optional[str] = None

    voltage_rating_v: Optional[float] = None

    telecommunication_use: Optional[bool] = None

    insulation_material: Optional[str] = None

    flat_cable: Optional[bool] = None

    core_diameter_mm: Optional[float] = None

    conductor_material: Optional[str] = None

    cable_length_m: Optional[float] = None

    data_transfer: Optional[bool] = None
    data_rate_gbps: Optional[float] = None

    charging_power_w: Optional[float] = None

    intended_use: Optional[str] = None

    quantity_pcs: Optional[int] = None
    gross_weight_kg: Optional[float] = None

    unit_value_idr: Optional[float] = None
    total_value_idr: Optional[float] = None

    origin: Optional[str] = None
    destination: Optional[str] = None


class ExtractionResult(BaseModel):
    updated_fields: UpdatedFields
    reasoning_summary: str


load_dotenv()

client = genai.Client(
    api_key=os.getenv("GEMINI_API_KEY")
)


SYSTEM_PROMPT = """
You are an AI Cross-Border Compliance Assistant.

Your task is to help users prepare for exporting goods
from Batam, Indonesia to Singapore.

For this prototype, the supported product is USB-C cable.

You are NOT a customs official and must not state
a Candidate HS Code as an official determination.

Your tasks are:

1. Understand the product description provided by the user.
2. Extract product information from the user's message.
3. Identify information that has been provided.
4. Identify information that is still missing when requested.
5. Generate clarification questions when necessary.
6. Do not ask for information that is not required.
7. Do not invent or assume information.
8. If information is not provided, use null.
9. Keep product attributes consistent with the existing product state.

The user-facing language for this prototype is English.
Always generate user-facing questions and explanations in English.
"""


def extract_product_info(
    user_message: str,
    current_state: ProductState,
    previous_question: str | None = None,
    current_field: str | None = None
):

    prompt = f"""
Current product state:

{current_state.model_dump_json(indent=2)}

Previous question asked by AI:

{previous_question}

Field being asked:

{current_field}

User's latest message:

{user_message}

Task:

Extract the information provided by the user.

Only extract information that is explicitly provided
or can be directly interpreted from the user's answer
to the previous question.

If the user gives a short answer such as:
- yes
- yeah
- correct
- it does
- it has

and the field being asked is boolean,
interpret the answer as TRUE when the previous question
clearly expects a yes/no answer.

If the user answers:
- no
- not yet
- none
- it does not
- it doesn't have

and the field being asked is boolean,
interpret the answer as FALSE when the previous question
clearly expects a yes/no answer.

Example:

Field being asked:
connector_present

Previous question:
"Does the cable have a connector on either or both ends?"

User:
"Yes."

Result:
connector_present = true


Example:

Field being asked:
telecommunication_use

Previous question:
"Is this cable intended for telecommunications use?"

User:
"No."

Result:
telecommunication_use = false


Example:

Field being asked:
connector_type

Previous question:
"What type of connector is attached to each end of the cable?"

User:
"USB-C on both ends."

Result:
connector_type = "USB-C on both ends"


Important rules:

- Do not invent information.
- Do not infer technical specifications that the user did not provide.
- Do not determine an HS Code.
- Do not determine whether the product is legally eligible for export.
- Do not determine whether the information is sufficient for compliance.
- Do not generate clarification questions in this step.
- Only extract information from the user's latest message.
- Preserve existing product information.
- If a field was not provided in the latest message, leave it as null.
"""

    response = client.models.generate_content(
        model="gemini-3.5-flash-lite",
        contents=[
            SYSTEM_PROMPT,
            prompt
        ],
        config={
            "response_mime_type": "application/json",
            "response_schema": ExtractionResult
        }
    )

    return response.parsed


def generate_question(
    missing_field: str,
    current_state: ProductState
):

    prompt = f"""
The following product information is still required:

{missing_field}

Current product state:

{current_state.model_dump_json(indent=2)}

Generate ONE clarification question in English
to obtain the required information from the user.

Rules:
- Ask only one question.
- Keep it short and easy to understand for a business owner.
- Use clear, natural English.
- Avoid unnecessary technical terminology.
- Ask only for the specified information.
- Do not ask for information that is not related to the specified field.
- Do not provide the answer.
- Do not explain why the information is needed.
- Do not mention HS Codes, regulations, or compliance decisions.
"""

    response = client.models.generate_content(
        model="gemini-3.5-flash-lite",
        contents=[
            SYSTEM_PROMPT,
            prompt
        ]
    )

    return response.text.strip()