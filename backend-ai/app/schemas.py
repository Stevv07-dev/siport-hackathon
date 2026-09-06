from typing import Optional
from pydantic import BaseModel


class ProductState(BaseModel):
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