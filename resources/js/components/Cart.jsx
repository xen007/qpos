import axios from "axios";
import React, { useState, useEffect } from "react";
import { toast } from "sonner";
import Swal from "sweetalert2";
import SuccessSound from "../sounds/beep-07a.mp3";
import WarningSound from "../sounds/beep-02.mp3";
import getErrorMessage from "../utils/getErrorMessage";
import playSound from "../utils/playSound";
import translate from "../utils/translate";
import { EmptyState } from "./WorkspaceUI";
import { Minus, Plus, Trash2 } from "lucide-react";

export default function Cart({ carts, setCartUpdated, cartUpdated }) {
    function increment(id) {
        axios
            .put("/admin/cart/increment", {
                id: id,
            })
            .then((res) => {
                setCartUpdated(previous => !previous);
                playSound(SuccessSound);
                toast.success(res?.data?.message);
            })
            .catch((err) => {
                playSound(WarningSound);
                toast.error(getErrorMessage(err));
            });
    }
    function decrement(id) {
        axios
            .put("/admin/cart/decrement", {
                id: id,
            })
            .then((res) => {
                setCartUpdated(previous => !previous);
                playSound(SuccessSound);
                toast.success(res?.data?.message);
            })
            .catch((err) => {
                playSound(WarningSound);
                toast.error(getErrorMessage(err));
            });
    }
    function destroy(id) {
        Swal.fire({
            title: translate("Are you sure you want to delete this item?"),
            showDenyButton: true,
            confirmButtonText: translate("Yes"),
            denyButtonText: translate("No"),
            customClass: {
                actions: "my-actions",
                cancelButton: "order-1 right-gap",
                confirmButton: "order-2",
                denyButton: "order-3",
            },
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .put("/admin/cart/delete", {
                        id: id,
                    })
                    .then((res) => {
                        setCartUpdated(previous => !previous);
                        playSound(SuccessSound);
                        toast.success(res?.data?.message);
                    })
                    .catch((err) => {
                        toast.error(getErrorMessage(err));
                    });
            } else if (result.isDenied) {
                return;
            }
        });
    }
    return (
        <section className="qpos-cart-items" aria-label={translate("Cart")}>
            {!carts.length && <EmptyState title={translate("Cart is empty")} description={translate("Select products to start")} />}
            {carts.map(item => <article className="qpos-cart-item" key={item.id}>
                <div className="qpos-cart-item-heading"><strong>{item.product.name}</strong>
                    <button type="button" className="qpos-icon-button qpos-icon-button-danger" aria-label={translate("Remove item") + ': ' + item.product.name} onClick={() => destroy(item.id)}><Trash2 size={18} aria-hidden="true" /></button>
                </div>
                <div className="qpos-cart-item-bottom">
                    <div className="qpos-stepper">
                        <button type="button" aria-label={translate("Decrease quantity")} onClick={() => decrement(item.id)}><Minus size={16} aria-hidden="true" /></button>
                        <output aria-label={translate("Quantity")}>{item.quantity}</output>
                        <button type="button" aria-label={translate("Increase quantity")} onClick={() => increment(item.id)}><Plus size={16} aria-hidden="true" /></button>
                    </div>
                    <span className="qpos-muted">{item.product.discounted_price}{item.product.price > item.product.discounted_price && <del>{item.product.price}</del>}</span>
                    <strong>{item.row_total}</strong>
                </div>
            </article>)}
        </section>
    );
}
